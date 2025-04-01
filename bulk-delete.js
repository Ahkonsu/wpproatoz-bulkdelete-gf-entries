document.addEventListener('DOMContentLoaded', function() {
    const startButton = document.getElementById('start-bulk-delete');
    const stopButton = document.getElementById('stop-bulk-delete');
    const resetButton = document.getElementById('reset-settings');
    const progressText = document.getElementById('progress-text');
    const progressBarFill = document.getElementById('progress-bar-fill');
    const loader = document.getElementById('loader');
    const formSelect = document.querySelector('select[name="wpproatoz_gf_bulk_delete_options[form_id]"]');
    let isRunning = false;

    if (!startButton) {
        console.error('Start button not found');
        return;
    }

    startButton.addEventListener('click', function(e) {
        e.preventDefault();
        if (isRunning) return;

        // Fetch current settings before showing confirmation
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
                startButton.disabled = true;
                stopButton.style.display = 'inline-block';
                progressText.textContent = 'Starting bulk delete...';
                loader.style.display = 'inline-block';
                progressBarFill.style.width = '0%';
                deleteOptionViaAjax('wpproatoz_gf_bulk_delete_stop');
                runBulkDelete(0);
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
        updateOptionViaAjax('wpproatoz_gf_bulk_delete_stop', '1');
        progressText.textContent = 'Stopping after current batch...';
        stopButton.disabled = true;
    });

    resetButton.addEventListener('click', function(e) {
        e.preventDefault();
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
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload(); // Refresh page to reflect defaults
            }
        });
    });

    formSelect.addEventListener('change', function() {
        const formId = this.value;
        if (!formId) {
            document.getElementById('entry-count-preview').textContent = '';
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
            if (data.success) {
                document.getElementById('entry-count-preview').textContent = `Total Entries to Delete: ${data.data.count}`;
            }
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
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            console.log('AJAX Response:', data);
            if (data.success) {
                const actionText = data.data.dry_run ? 'Would Delete' : 'Deleted';
                progressText.textContent = `${actionText}: ${data.data.total_deleted} / ${data.data.total_entries} (${data.data.percentage}%) - Remaining: ${data.data.remaining}`;
                progressBarFill.style.width = `${data.data.percentage}%`;
                loader.style.display = 'inline-block';
                if (data.data.is_stopped) {
                    progressText.textContent += ' - Stopped by user.';
                    loader.style.display = 'none';
                    resetUI();
                } else if (data.data.has_more) {
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
            console.error('AJAX Error:', error);
            progressText.textContent = 'AJAX Error: ' + error.message;
            loader.style.display = 'none';
            resetUI();
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
        fetch(wpproatoz_gf_ajax.ajax_url, {
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
        fetch(wpproatoz_gf_ajax.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(data).toString()
        }).then(() => console.log(`Deleted ${option}`));
    }

    console.log('Bulk Delete JS Loaded');
});