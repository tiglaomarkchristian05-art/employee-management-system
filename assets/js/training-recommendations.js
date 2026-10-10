(() => {
    function init() {
        const section = document.getElementById('aiRecommendationsSection');
        if (!section) return;

        const csrfToken = section.dataset.csrfToken || '';
        const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({
            '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
        })[char]);
        const error = message => {
            if (typeof window.showErrorDialog === 'function') window.showErrorDialog(message);
            else if (window.Swal) window.Swal.fire({ icon: 'error', title: 'Error', text: message });
            else window.alert(message);
        };
        const success = message => {
            if (typeof window.showToast === 'function') window.showToast('success', message);
            else window.alert(message);
            window.setTimeout(() => window.location.reload(), 650);
        };

        async function post(page, values, button) {
            if (button) button.disabled = true;
            try {
                const body = values instanceof URLSearchParams ? values : new URLSearchParams(values);
                if (!body.has('csrf_token')) body.set('csrf_token', csrfToken);
                const response = await fetch('index.php?page=' + encodeURIComponent(page), {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8', 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: body.toString()
                });
                const result = await response.json();
                if (!response.ok || result.status !== 'success') throw new Error(result.message || 'The action could not be completed.');
                success(result.message || 'Saved successfully.');
            } catch (cause) {
                error(cause instanceof SyntaxError ? 'The server returned an unexpected response. Please refresh and try again.' : cause.message);
                if (button) button.disabled = false;
            }
        }

        section.addEventListener('click', async event => {
            const button = event.target.closest('button');
            if (!button || !section.contains(button)) return;

            if (button.matches('.ai-explain')) {
                let breakdown = {};
                try { breakdown = JSON.parse(button.dataset.breakdown || '{}'); } catch (_) { /* Reason remains available. */ }
                const lines = Object.entries(breakdown).map(([key, value]) => `${key.replace(/_/g, ' ')}: ${value}`);
                const reason = button.dataset.reason || 'No explanation is available for this recommendation.';
                if (window.Swal) {
                    window.Swal.fire({
                        title: button.dataset.title || 'Why this training?',
                        html: `<div class="text-start"><p>${escapeHtml(reason)}</p>${lines.length ? `<hr><small>${lines.map(escapeHtml).join('<br>')}</small>` : ''}</div>`,
                        confirmButtonText: 'Close',
                        width: 600
                    });
                } else window.alert(`${button.dataset.title || 'Why this training?'}\n\n${reason}${lines.length ? '\n\n' + lines.join('\n') : ''}`);
                return;
            }

            if (button.matches('.ai-review')) {
                await post('training_recommendations_review', { id: button.dataset.id, decision: button.dataset.decision }, button);
            } else if (button.matches('.ai-dismiss')) {
                let reason;
                if (window.Swal) {
                    const answer = await window.Swal.fire({ title: 'Dismiss recommendation?', input: 'textarea', inputLabel: 'Reason (optional)', showCancelButton: true, confirmButtonText: 'Dismiss', confirmButtonColor: '#dc3545' });
                    if (!answer.isConfirmed) return;
                    reason = answer.value || '';
                } else {
                    if (!window.confirm('Dismiss this recommendation?')) return;
                    reason = window.prompt('Reason (optional):', '') || '';
                }
                await post('training_recommendations_review', { id: button.dataset.id, decision: 'Dismissed', dismissed_reason: reason }, button);
            } else if (button.matches('.ai-assign')) {
                const confirmed = window.Swal
                    ? (await window.Swal.fire({ title: 'Assign recommended training?', text: 'This will create an employee training assignment.', icon: 'question', showCancelButton: true, confirmButtonText: 'Assign' })).isConfirmed
                    : window.confirm('Assign this recommended training to the employee?');
                if (confirmed) await post('training_recommendations_assign', { id: button.dataset.id }, button);
            } else if (button.matches('.ai-apply')) {
                const confirmed = window.Swal
                    ? (await window.Swal.fire({ title: 'Apply for this training?', icon: 'question', showCancelButton: true, confirmButtonText: 'Apply' })).isConfirmed
                    : window.confirm('Apply for this training?');
                if (confirmed) await post('training_register', { course_id: button.dataset.id }, button);
            }
        });

        const forms = [
            ['analysisForm', 'training_recommendations_analyze'],
            ['historicalForm', 'training_history_store']
        ];
        forms.forEach(([id, page]) => {
            const form = document.getElementById(id);
            if (!form) return;
            form.addEventListener('submit', event => {
                event.preventDefault();
                post(page, new URLSearchParams(new FormData(form)), form.querySelector('[type="submit"], button:not([type])'));
            });
        });

        ['aiDepartmentFilter', 'aiPositionFilter', 'aiPriorityFilter'].forEach(id => {
            const filter = document.getElementById(id);
            if (!filter) return;
            filter.addEventListener('change', () => {
                const $ = window.jQuery;
                const table = document.getElementById('aiRecommendationsTable');
                if (!$ || !table || !$.fn.DataTable || !$.fn.DataTable.isDataTable(table)) return;
                const grid = $(table).DataTable();
                const department = document.getElementById('aiDepartmentFilter');
                const position = document.getElementById('aiPositionFilter');
                const priority = document.getElementById('aiPriorityFilter');
                if (department) grid.column(1).search(department.value, false, false);
                if (position) grid.column(2).search(position.value, false, false);
                grid.column(department ? 5 : 2).search(priority.value, false, false).draw();
            });
        });
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
