document.addEventListener('DOMContentLoaded', function() {
    const startButton = document.getElementById('start-bulk-delete');
    const stopButton = document.getElementById('stop-bulk-delete');
    const resetButton = document.getElementById('reset-settings');
    const progressText = document.getElementById('progress-text');
    const progressBarFill = document.getElementById('progress-bar-fill');
    const loader = document.getElementById('loader');
    const formSelect = document.querySelector('select[name="wpproatoz_gf_bulk_delete_options[form_id]"]');
    const entryCountPreview = document.getElementById('entry-count-preview');
    let isRunning = false;
    let retryCount = 0;
    const maxRetries = 3;

    if (!startButton || !stopButton || !resetButton || !formSelect || !entryCountPreview) {
        console.error('Required elements not found:', { startButton, stopButton, resetButton, formSelect, entryCountPreview });
        return;
    }

    // Wait for wpproatoz_gf_ajax to be defined
    function initWhenReady() {
        if (typeof wpproatoz_gf_ajax === 'undefined') {
            console.warn('wpproatoz_gf_ajax not defined yet, retrying in 100ms');
            setTimeout(initWhenReady, 100);
            return;
        }

        console.log('Bulk Delete JS Loaded, wpproatoz_gf_ajax:', wpproatoz_gf_ajax);

        startButton.addEventListener('click', function(e) {
            e.preventDefault();
            if (isRunning) return;

            console.log('Start Bulk Delete clicked');
            fetch(wpproatoz_gf_ajax.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'wpproatoz_gf_get_settings',
                    nonce: wpproatoz_gf_ajax.nonce
                }).toString()
            })
            .then(response => response.json())
            .then(data => {
                console.log('Settings fetch response:', data);
                if (data.success) {
                    const formId = data.data.form_id;
                    const formTitle = data.data.form_title;
                    const dryRun = data.data.dry_run;

                    if (!formId) {
                        alert('Please select a form in the settings before proceeding.');
                        return;
                    }

                    const actionText = dryRun ? 'simulate deletion for' : 'delete entries from';
                    const dryRunNotice = dryRun ? ' (Dry Run mode is enabled)' : '';
                    const confirmation = confirm(`Are you sure you want to ${actionText} "${formTitle}"? This cannot be undone${dryRunNotice}.`);
                    if (!confirmation) return;

                    isRunning = true;
                    retryCount = 0;
                    startButton.disabled = true;
                    stopButton.style.display = 'inline-block';
                    progressText.textContent = 'Starting bulk delete...';
                    loader.style.display = 'inline-block';
                    progressBarFill.style.width = '0%';

                    deleteOptionViaAjax('wpproatoz_gf_bulk_delete_stop').then(() => {
                        console.log('Stop flag cleared, starting bulk delete');
                        setTimeout(() => runBulkDelete(0), 1000);
                    }).catch(error => {
                        console.error('Failed to clear stop flag:', error);
                        progressText.textContent = 'Error initializing: ' + error.message;
                        resetUI();
                    });
                } else {
                    alert('Error fetching settings: ' + data.data.message);
                }
            })
            .catch(error => {
                console.error('Error fetching settings:', error);
                alert('Error fetching settings. Please try again.');
            });
        });

        stopButton.addEventListener('click', function(e) {
            e.preventDefault();
            if (!isRunning) return;
            if (!confirm('Are you sure you want to stop the bulk delete process?')) return;
            console.log('Stop Bulk Delete clicked');
            updateOptionViaAjax('wpproatoz_gf_bulk_delete_stop', '1');
            progressText.textContent = 'Stopping after current batch...';
            stopButton.disabled = true;
        });

        resetButton.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Reset to Defaults clicked');
            if (!confirm('Are you sure you want to reset all settings to defaults?')) return;

            fetch(wpproatoz_gf_ajax.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'wpproatoz_gf_reset_settings',
                    nonce: wpproatoz_gf_ajax.nonce
                }).toString()
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                console.log('Reset response:', data);
                if (data.success) {
                    alert('Settings reset to defaults. Reloading page...');
                    location.reload();
                } else {
                    alert('Failed to reset settings: ' + (data.data?.message || 'Unknown error'));
                    console.error('Reset failed:', data);
                }
            })
            .catch(error => {
                console.error('Reset error:', error);
                alert('Error resetting settings: ' + error.message);
            });
        });

        formSelect.addEventListener('change', function() {
            const formId = this.value;
            console.log('Form select changed to:', formId);
            if (!formId) {
                entryCountPreview.textContent = '';
                return;
            }
            fetch(wpproatoz_gf_ajax.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({
                    action: 'wpproatoz_gf_get_entry_count',
                    nonce: wpproatoz_gf_ajax.nonce,
                    form_id: formId
                }).toString()
            })
            .then(response => response.json())
            .then(data => {
                console.log('Entry count response:', data);
                if (data.success) {
                    entryCountPreview.textContent = `Total Entries to Delete: ${data.data.count}`;
                } else {
                    entryCountPreview.textContent = 'Error fetching entry count';
                    console.error('Entry count error:', data.data.message);
                }
            })
            .catch(error => {
                console.error('Entry count fetch error:', error);
                entryCountPreview.textContent = 'Error fetching entry count';
            });
        });

        function runBulkDelete(offset) {
            const data = {
                action: 'wpproatoz_gf_bulk_delete_process',
                nonce: wpproatoz_gf_ajax.nonce,
                offset: offset
            };

            fetch(wpproatoz_gf_ajax.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(data).toString()
            })
            .then(response => {
                if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
                return response.json();
            })
            .then(data => {
                console.log('Bulk delete response:', data);
                if (data.success) {
                    retryCount = 0;
                    const actionText = data.data.dry_run ? 'Would Delete' : 'Deleted';
                    progressText.textContent = `${actionText}: ${data.data.total_deleted} / ${data.data.total_entries} (${data.data.percentage}%) - Remaining: ${data.data.remaining}`;
                    progressBarFill.style.width = `${data.data.percentage}%`;
                    loader.style.display = 'inline-block';
                    if (data.data.is_stopped) {
                        progressText.textContent += ' - Stopped by user.';
                        loader.style.display = 'none';
                        resetUI();
                    } else if (data.data.has_more) {
                        console.log(`Continuing with offset ${data.data.offset}, entries fetched: ${data.data.debug.entry_count}`);
                        setTimeout(() => runBulkDelete(data.data.offset), wpproatoz_gf_ajax.pause_time);
                    } else {
                        progressText.textContent += ' - Bulk ' + (data.data.dry_run ? 'simulation' : 'delete') + ' completed!';
                        loader.style.display = 'none';
                        resetUI();
                    }
                } else {
                    progressText.textContent = 'Error: ' + data.data.message;
                    loader.style.display = 'none';
                    resetUI();
                }
            })
            .catch(error => {
                console.error('Bulk delete error:', error);
                if (retryCount < maxRetries) {
                    retryCount++;
                    progressText.textContent = `Retrying (${retryCount}/${maxRetries}) after error: ${error.message}`;
                    setTimeout(() => runBulkDelete(offset), 5000);
                } else {
                    progressText.textContent = `Failed after ${maxRetries} retries: ${error.message}`;
                    loader.style.display = 'none';
                    resetUI();
                }
            });
        }

        function resetUI() {
            isRunning = false;
            startButton.disabled = false;
            stopButton.style.display = 'none';
            stopButton.disabled = false;
        }

        function updateOptionViaAjax(option, value) {
            const data = {
                action: 'wpproatoz_gf_update_option',
                nonce: wpproatoz_gf_ajax.nonce,
                option: option,
                value: value
            };
            return fetch(wpproatoz_gf_ajax.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(data).toString()
            }).then(() => console.log(`Updated ${option} to ${value}`));
        }

        function deleteOptionViaAjax(option) {
            const data = {
                action: 'wpproatoz_gf_delete_option',
                nonce: wpproatoz_gf_ajax.nonce,
                option: option
            };
            return fetch(wpproatoz_gf_ajax.ajax_url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams(data).toString()
            }).then(() => console.log(`Deleted ${option}`));
        }
    }

    // Start initialization
    initWhenReady();
});