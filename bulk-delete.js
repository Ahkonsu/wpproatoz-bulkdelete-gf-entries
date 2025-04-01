document.addEventListener('DOMContentLoaded', function() {
    const startButton = document.getElementById('start-bulk-delete');
    const stopButton = document.getElementById('stop-bulk-delete');
    const progressText = document.getElementById('progress-text');
    const progressBarFill = document.getElementById('progress-bar-fill');
    let isRunning = false;

    if (!startButton) {
        console.error('Start button not found');
        return;
    }

    startButton.addEventListener('click', function(e) {
        e.preventDefault();
        if (!confirm('Are you sure you want to delete entries? This cannot be undone.')) return;
        if (isRunning) return;
        isRunning = true;
        startButton.disabled = true;
        stopButton.style.display = 'inline-block';
        progressText.textContent = 'Starting bulk delete...';
        progressBarFill.style.width = '0%';
        deleteOptionViaAjax('wpproatoz_gf_bulk_delete_stop');
        runBulkDelete(0);
    });

    stopButton.addEventListener('click', function(e) {
        e.preventDefault();
        if (!isRunning) return;
        updateOptionViaAjax('wpproatoz_gf_bulk_delete_stop', '1');
        progressText.textContent = 'Stopping after current batch...';
        stopButton.disabled = true;
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
                progressText.textContent = `Deleted: ${data.data.total_deleted} / ${data.data.total_entries} (${data.data.percentage}%) - Remaining: ${data.data.remaining}`;
                progressBarFill.style.width = `${data.data.percentage}%`;
                if (data.data.is_stopped) {
                    progressText.textContent += ' - Stopped by user.';
                    resetUI();
                } else if (data.data.has_more) {
                    setTimeout(() => runBulkDelete(data.data.offset), wpproatoz_gf_ajax.pause_time);
                } else {
                    progressText.textContent += ' - Bulk delete completed!';
                    resetUI();
                }
            } else {
                progressText.textContent = 'Error: ' + data.data.message;
                resetUI();
            }
        })
        .catch(error => {
            console.error('AJAX Error:', error);
            progressText.textContent = 'AJAX Error: ' + error.message;
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