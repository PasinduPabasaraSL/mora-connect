<?php

?>
<div class="confirm" id="confirmDialog" hidden>
    <div class="confirm-backdrop" data-confirm-cancel></div>

    <div class="confirm-panel" role="dialog" aria-modal="true"
         aria-labelledby="confirmTitle" aria-describedby="confirmBody">
        <div class="confirm-icon" aria-hidden="true">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 9v4M12 17h.01"></path>
                <path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0Z"></path>
            </svg>
        </div>

        <h2 id="confirmTitle">Are you sure?</h2>
        <p id="confirmBody">This cannot be undone.</p>

        <div class="confirm-actions">
            <button type="button" class="btn" id="confirmCancel" data-confirm-cancel>Cancel</button>
            <button type="button" class="btn btn-danger" id="confirmAccept">Delete</button>
        </div>
    </div>
</div>
