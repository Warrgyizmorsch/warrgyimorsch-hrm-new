<!-- Quick View Modal -->
<div class="modal fade pm-modal" id="quickViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="feather-info text-primary"></i>
                    <span id="qvProjectName">Project Name</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <label class="pm-modal-label">Description</label>
                <div id="qvDescription" class="pm-modal-panel"></div>

                <div class="mb-4" id="qvDocumentsContainer" style="display: none;">
                    <label class="pm-modal-label">Attached Documents</label>
                    <div id="qvDocuments"></div>
                </div>

                <hr class="pm-modal-divider">

                <div id="qvTeamList"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="zoho-btn-outline w-100" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Task Progress Analysis Modal -->
<div class="modal fade pm-modal" id="taskProgressModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="feather-clipboard text-success"></i>
                    <span id="tpProjectName">Project Task Progress</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="tpList" class="pm-task-analysis"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="zoho-btn-outline w-100" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
