/* Shared helpers for the Procurement module pages. */
(function () {
    window.procEsc = function (value) {
        return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
            return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[ch];
        });
    };

    window.procMoney = function (value) {
        return '₱' + Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    };

    window.procQty = function (value) {
        var n = Number(value || 0);
        return (Math.round(n * 100) / 100).toLocaleString();
    };

    window.procDate = function (value) {
        if (!value) return '—';
        return String(value).slice(0, 10);
    };

    window.procDateTime = function (value) {
        if (!value) return '—';
        return String(value).replace('T', ' ').slice(0, 16);
    };

    var statusMap = {
        // purchase request
        draft: { label: 'Draft', cls: 'badge-secondary' },
        budget_review: { label: 'Budget Review', cls: 'badge-info' },
        pending_approval: { label: 'Pending Approval', cls: 'badge-warning' },
        revision_requested: { label: 'Revision Requested', cls: 'badge-info' },
        approved: { label: 'Approved', cls: 'badge-success' },
        sourcing: { label: 'Sourcing / RFQ', cls: 'badge-info' },
        supplier_selected: { label: 'Supplier Selected', cls: 'badge-primary' },
        closed: { label: 'Closed', cls: 'badge-success' },
        rejected: { label: 'Rejected', cls: 'badge-danger' },
        cancelled: { label: 'Cancelled', cls: 'badge-secondary' },
        // purchase order
        pending_finance: { label: 'Pending Finance', cls: 'badge-warning' },
        ordered: { label: 'Ordered', cls: 'badge-primary' },
        acknowledged: { label: 'Acknowledged', cls: 'badge-info' },
        partially_received: { label: 'Partially Received', cls: 'badge-warning' },
        received: { label: 'Received', cls: 'badge-success' },
        // invoice / payment
        unpaid: { label: 'Unpaid', cls: 'badge-danger' },
        partial: { label: 'Partially Paid', cls: 'badge-warning' },
        paid: { label: 'Paid', cls: 'badge-success' },
        // three-way match
        matched: { label: 'Matched', cls: 'badge-success' },
        held: { label: 'On Hold', cls: 'badge-danger' },
        pending: { label: 'Pending', cls: 'badge-secondary' },
        // budget status
        not_required: { label: 'Not Required', cls: 'badge-secondary' },
        checking: { label: 'Checking', cls: 'badge-info' }
    };

    window.procStatus = function (status) {
        return statusMap[status] || { label: status || '—', cls: 'badge-secondary' };
    };

    window.procBadge = function (status) {
        var s = window.procStatus(status);
        return '<span class="badge ' + s.cls + '">' + window.procEsc(s.label) + '</span>';
    };

    window.procGet = function (url) {
        return fetch(url).then(function (r) { return r.json(); });
    };

    window.procPost = function (action, data) {
        return fetch('?action=' + action, { method: 'POST', body: data }).then(function (r) { return r.json(); });
    };

    window.procToast = function (type, title, message) {
        if (window.showToast) { window.showToast(type, title, message || '', 3000); }
        else { alert(title + (message ? ' — ' + message : '')); }
    };

    window.procOpenModal = function (id) {
        var el = document.getElementById(id);
        if (el) el.classList.add('show');
    };

    window.procCloseModal = function (id) {
        if (window.closeModal) { window.closeModal(id); return; }
        var el = document.getElementById(id);
        if (el) el.classList.remove('show');
    };

    // Show the duplicate/unnecessary-purchase warnings and, if acknowledged,
    // re-submit the same FormData with the acknowledgement flag set.
    window.procConfirmWarnings = function (warnings, formData, retry) {
        var html = '<div class="alert alert-warning" style="text-align:left;"><strong>Please review before submitting:</strong><ul style="margin:8px 0 0 18px;">'
            + warnings.map(function (w) { return '<li>' + window.procEsc(w) + '</li>'; }).join('')
            + '</ul></div><p style="margin-top:10px;">Submit anyway?</p>';
        if (window.showConfirm) {
            window.showConfirm({
                title: 'Possible duplicate or unnecessary purchase',
                message: html,
                confirmText: 'Submit anyway',
                variant: 'warning',
                onConfirm: function () { formData.set('acknowledge_warnings', '1'); retry(formData); }
            });
        } else if (window.confirm(warnings.join('\n') + '\n\nSubmit anyway?')) {
            formData.set('acknowledge_warnings', '1');
            retry(formData);
        }
    };
})();
