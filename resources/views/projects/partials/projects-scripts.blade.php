<script>
    window.filterLeadList = function (input) {
        var value = input.value.toLowerCase().trim();
        var dropdown = input.closest('.dropdown-menu');
        if (!dropdown) return;
        var items = dropdown.querySelectorAll('li:not(.pm-dropdown-search)');
        var header = dropdown.querySelector('.pm-dropdown-search');
        for (var i = 0; i < items.length; i++) {
            var li = items[i];
            var text = (li.innerText || li.textContent).toLowerCase();
            if (text.indexOf(value) > -1) {
                li.style.setProperty('display', 'block', 'important');
                if (value !== '' && header) {
                    header.parentNode.insertBefore(li, header.nextSibling);
                }
            } else {
                li.style.setProperty('display', 'none', 'important');
            }
        }
    };

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.dropdown-toggle').forEach(function (el) {
            new bootstrap.Dropdown(el, { popperConfig: { strategy: 'fixed' } });
        });

        document.querySelectorAll('.btn-quick-view').forEach(function (btn) {
            btn.addEventListener('click', function () {
                showQuickView(btn.dataset.project ? JSON.parse(btn.dataset.project) : null);
            });
        });

        var quickSearch = document.getElementById('pmQuickSearch');
        if (quickSearch) {
            quickSearch.addEventListener('input', function () {
                var q = this.value.toLowerCase().trim();
                document.querySelectorAll('#projectList tbody tr.single-item').forEach(function (row) {
                    var blob = row.dataset.search || row.dataset.name || '';
                    row.style.display = !q || blob.includes(q) ? '' : 'none';
                });
            });
        }

        var checkAll = document.getElementById('checkAllProject');
        var bulkBar = document.getElementById('pmBulkBar');
        var bulkCount = document.getElementById('pmBulkCount');
        var bulkDeleteBtn = document.getElementById('pmBulkDeleteBtn');
        var rowChecks = document.querySelectorAll('.pm-project-check');

        function refreshBulkBar() {
            var selected = document.querySelectorAll('.pm-project-check:checked');
            if (!bulkBar || !bulkCount) return;
            bulkBar.classList.toggle('is-visible', selected.length > 0);
            bulkCount.textContent = selected.length + ' selected';
        }

        checkAll?.addEventListener('change', function () {
            rowChecks.forEach(function (cb) { cb.checked = checkAll.checked; });
            refreshBulkBar();
        });

        rowChecks.forEach(function (cb) {
            cb.addEventListener('change', refreshBulkBar);
        });

        bulkDeleteBtn?.addEventListener('click', function () {
            var ids = Array.from(document.querySelectorAll('.pm-project-check:checked')).map(function (cb) { return cb.value; });
            if (!ids.length) return;

            Swal.fire({
                title: 'Delete selected projects?',
                text: ids.length + ' project(s) will be permanently removed.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete',
                cancelButtonText: 'Cancel',
                buttonsStyling: false,
                customClass: {
                    confirmButton: 'btn btn-primary px-4 me-2',
                    cancelButton: 'btn btn-secondary px-4'
                }
            }).then(function (result) {
                if (!result.isConfirmed) return;
                fetch('{{ route("projects.bulk-delete") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ ids: ids })
                })
                    .then(function (res) { return res.json(); })
                    .then(function () { window.location.reload(); })
                    .catch(function () { Swal.fire('Error', 'Could not delete projects.', 'error'); });
            });
        });

        if (typeof feather !== 'undefined') feather.replace();
    });

    window.showQuickView = function (project) {
        if (!project) return;
        var modal = new bootstrap.Modal(document.getElementById('quickViewModal'));
        document.getElementById('qvProjectName').innerText = project.name;
        document.getElementById('qvDescription').innerHTML = project.description || '<p class="text-muted mb-0">No description available.</p>';

        var qvDocsContainer = document.getElementById('qvDocumentsContainer');
        var qvDocsList = document.getElementById('qvDocuments');
        qvDocsContainer.style.display = 'none';
        qvDocsList.innerHTML = '';

        var documents = project.documents || [];
        if (Array.isArray(documents) && documents.length > 0) {
            var docsHtml = '<div class="row g-3">';
            documents.forEach(function (doc) {
                var fileName = doc.split('/').pop();
                var fileUrl = '/storage/' + doc;
                docsHtml += '<div class="col-md-4"><div class="pm-doc-chip"><i class="feather-file-text"></i><a href="' + fileUrl + '" target="_blank">' + fileName + '</a></div></div>';
            });
            docsHtml += '</div>';
            qvDocsList.innerHTML = docsHtml;
            qvDocsContainer.style.display = 'block';
        }

        var leaders = project.leaders || [];
        var members = project.members || [];
        var allEmps = @json($employees);
        var teamHtml = '';

        if (leaders.length > 0) {
            teamHtml += '<h6 class="pm-modal-subtitle">Project Leads</h6><div class="row g-2 mb-3">';
            allEmps.forEach(function (emp) {
                if (leaders.includes(emp.id.toString()) || leaders.includes(emp.id)) {
                    teamHtml += '<div class="col-md-6"><div class="pm-team-chip"><span class="pm-team-avatar">' + emp.name.charAt(0) + '</span>' + emp.name + '</div></div>';
                }
            });
            teamHtml += '</div>';
        }
        if (members.length > 0) {
            teamHtml += '<h6 class="pm-modal-subtitle">Team Members</h6><div class="row g-2">';
            allEmps.forEach(function (emp) {
                if (members.includes(emp.id.toString()) || members.includes(emp.id)) {
                    teamHtml += '<div class="col-md-4"><div class="pm-team-chip pm-team-chip--muted"><span class="pm-team-avatar">' + emp.name.charAt(0) + '</span>' + emp.name + '</div></div>';
                }
            });
            teamHtml += '</div>';
        }
        if (teamHtml === '') teamHtml = '<div class="pm-empty-inline">No team members assigned.</div>';
        document.getElementById('qvTeamList').innerHTML = teamHtml;
        modal.show();
    };

    window.confirmDeleteProject = function (form, name) {
        Swal.fire({
            title: 'Delete project?',
            text: 'You are about to delete: ' + name,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            buttonsStyling: false,
            customClass: {
                confirmButton: 'btn btn-primary px-4 me-2',
                cancelButton: 'btn btn-secondary px-4'
            }
        }).then(function (result) {
            if (result.isConfirmed) form.submit();
        });
    };

    window.showTaskProgress = function (projectId) {
        var modalEl = document.getElementById('taskProgressModal');
        var listContainer = document.getElementById('tpList');
        listContainer.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"></div><p class="mt-2 text-muted">Analyzing tasks...</p></div>';

        var modal = new bootstrap.Modal(modalEl);
        modal.show();

        fetch('/projects/' + projectId + '/tasks-summary')
            .then(function (res) { return res.json(); })
            .then(function (data) {
                document.getElementById('tpProjectName').innerText = data.project_name;
                if (!data.tasks || data.tasks.length === 0) {
                    listContainer.innerHTML = '<div class="pm-empty-inline">No tasks found for this project.</div>';
                    return;
                }

                var html = '';
                var employeeWork = {};

                data.tasks.forEach(function (task) {
                    var empName = task.employee ? task.employee.name : 'Unassigned';
                    if (!employeeWork[empName]) employeeWork[empName] = {};

                    if (task.follow_ups && task.follow_ups.length > 0) {
                        task.follow_ups.forEach(function (fu) {
                            var date = new Date(fu.created_at).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                            if (!employeeWork[empName][date]) employeeWork[empName][date] = { tasks: {}, dailyTotal: 0 };

                            var taskTitle = task.task_title;
                            if (!employeeWork[empName][date].tasks[taskTitle]) {
                                employeeWork[empName][date].tasks[taskTitle] = { updates: [], taskTotal: 0 };
                            }

                            var time = 0;
                            var matches = (fu.time_taken || '').match(/[+-]?([0-9]*[.])?[0-9]+/);
                            if (matches) time = parseFloat(matches[0]);

                            employeeWork[empName][date].tasks[taskTitle].updates.push({
                                description: fu.work_description,
                                time: time,
                                timestamp: new Date(fu.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
                            });
                            employeeWork[empName][date].tasks[taskTitle].taskTotal += time;
                            employeeWork[empName][date].dailyTotal += time;
                        });
                    }
                });

                for (var empName in employeeWork) {
                    var dates = employeeWork[empName];
                    html += '<div class="pm-analysis-group"><div class="pm-analysis-head"><span class="pm-team-avatar">' + empName.charAt(0) + '</span><div><strong>' + empName + '</strong><small>Contributor</small></div></div>';

                    var sortedDates = Object.keys(dates).sort(function (a, b) { return new Date(b) - new Date(a); });
                    sortedDates.forEach(function (date) {
                        var dayData = dates[date];
                        var dTotal = dayData.dailyTotal;
                        var dTh = Math.floor(dTotal);
                        var dTm = Math.round((dTotal - dTh) * 60);
                        var dTimeStr = (dTh > 0 ? dTh + 'h ' : '') + (dTm > 0 ? dTm + 'm' : (dTh === 0 ? '0m' : ''));

                        html += '<div class="pm-analysis-day"><div class="pm-analysis-day-head"><span>' + date + '</span><span class="pm-badge pm-badge--muted">Day total: ' + dTimeStr + '</span></div>';

                        for (var title in dayData.tasks) {
                            var taskData = dayData.tasks[title];
                            var tTotal = taskData.taskTotal;
                            var tTh = Math.floor(tTotal);
                            var tTm = Math.round((tTotal - tTh) * 60);
                            var tTimeStr = (tTh > 0 ? tTh + 'h ' : '') + (tTm > 0 ? tTm + 'm' : (tTh === 0 ? '0m' : ''));

                            html += '<div class="pm-analysis-task"><div class="pm-analysis-task-head"><strong>' + title + '</strong><span class="pm-badge pm-badge--info">' + tTimeStr + '</span></div>';

                            taskData.updates.forEach(function (update) {
                                var uh = Math.floor(update.time);
                                var um = Math.round((update.time - uh) * 60);
                                var uTimeStr = (uh > 0 ? uh + 'h ' : '') + (um > 0 ? um + 'm' : (uh === 0 ? '0m' : ''));
                                html += '<div class="pm-analysis-update"><span class="pm-analysis-update-meta">' + update.timestamp + ' · ' + uTimeStr + '</span><div>' + (update.description || '<em>No details</em>') + '</div></div>';
                            });

                            html += '</div>';
                        }
                        html += '</div>';
                    });
                    html += '</div>';
                }
                listContainer.innerHTML = html;
            });
    };

    window.updateProjectStatus = function (id, status) {
        updateProjectField(id, { status: status }, 'Status updated');
        setTimeout(function () { location.reload(); }, 400);
    };

    window.updateProjectLeads = function (id, leaderIds) {
        updateProjectField(id, { leaders: leaderIds }, 'Leads updated');
        setTimeout(function () { location.reload(); }, 400);
    };

    window.toggleProjectLead = function (id, currentLeaders, leaderId) {
        currentLeaders = Array.isArray(currentLeaders) ? currentLeaders.map(Number) : [];
        leaderId = Number(leaderId);
        var updatedLeaders = currentLeaders.includes(leaderId)
            ? currentLeaders.filter(function (lid) { return lid !== leaderId; })
            : currentLeaders.concat(leaderId);
        window.updateProjectLeads(id, updatedLeaders);
    };

    window.updateProjectMembers = function (id, memberIds) {
        updateProjectField(id, { members: memberIds }, 'Members updated');
        setTimeout(function () { location.reload(); }, 400);
    };

    window.toggleProjectMember = function (id, currentMembers, memberId) {
        currentMembers = Array.isArray(currentMembers) ? currentMembers.map(Number) : [];
        memberId = Number(memberId);
        var updatedMembers = currentMembers.includes(memberId)
            ? currentMembers.filter(function (mid) { return mid !== memberId; })
            : currentMembers.concat(memberId);
        updateProjectMembers(id, updatedMembers);
    };

    function updateProjectField(id, data, successTitle) {
        var url = '{{ route("projects.update-field", ["project" => ":id"]) }}'.replace(':id', id);
        $.ajax({
            url: url,
            type: 'PATCH',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
            data: data,
            success: function () {
                if (typeof Toast !== 'undefined') Toast.fire({ icon: 'success', title: successTitle });
            }
        });
    }

    var pmDragSlug = null;

    window.pmKanbanDragStart = function (e) {
        pmDragSlug = e.target.closest('.pm-kanban-card')?.dataset.slug || null;
        e.target.closest('.pm-kanban-card')?.classList.add('is-dragging');
    };

    window.pmKanbanAllowDrop = function (e) {
        e.preventDefault();
        e.currentTarget.classList.add('pm-kanban-drop-hover');
    };

    window.pmKanbanDragLeave = function (e) {
        e.currentTarget.classList.remove('pm-kanban-drop-hover');
    };

    window.pmKanbanDrop = function (e, status) {
        e.preventDefault();
        e.currentTarget.classList.remove('pm-kanban-drop-hover');
        document.querySelectorAll('.pm-kanban-card.is-dragging').forEach(function (el) { el.classList.remove('is-dragging'); });
        if (!pmDragSlug) return;
        updateProjectStatus(pmDragSlug, status);
        pmDragSlug = null;
    };
</script>
