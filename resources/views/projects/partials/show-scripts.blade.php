<script src="{{ asset('assets/vendors/js/apexcharts.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var chartEl = document.querySelector('#project-progress-chart');
        if (chartEl && typeof ApexCharts !== 'undefined') {
            new ApexCharts(chartEl, {
                series: [{{ $projectMetrics['progress'] ?? $project->display_progress }}],
                chart: { height: 220, type: 'radialBar', sparkline: { enabled: false } },
                plotOptions: {
                    radialBar: {
                        hollow: { size: '68%' },
                        dataLabels: {
                            name: { show: true, fontSize: '12px', color: '#64748b', offsetY: 24 },
                            value: {
                                offsetY: -8,
                                fontSize: '24px',
                                fontWeight: 700,
                                formatter: function (val) { return val + '%'; }
                            }
                        }
                    }
                },
                colors: ['#1070e0'],
                labels: ['Task progress'],
                stroke: { lineCap: 'round' }
            }).render();
        }

        function updateProjectTimers() {
            var now = new Date();
            document.querySelectorAll('.task-timer').forEach(function (timer) {
                var dataEnd = timer.getAttribute('data-end');
                var dataStart = timer.getAttribute('data-start');

                if (dataEnd) {
                    var end = new Date(dataEnd);
                    if (now < end) {
                        var diff = end - now;
                        var days = Math.floor(diff / 86400000); diff -= days * 86400000;
                        var hours = Math.floor(diff / 3600000); diff -= hours * 3600000;
                        var mins = Math.floor(diff / 60000); diff -= mins * 60000;
                        var secs = Math.floor(diff / 1000);
                        timer.innerHTML = '<i class="feather-clock"></i> ' + days + 'd ' + hours + 'h ' + mins + 'm ' + secs + 's left';
                    } else {
                        var overdue = now - end;
                        var od = Math.floor(overdue / 86400000); overdue -= od * 86400000;
                        var oh = Math.floor(overdue / 3600000); overdue -= oh * 3600000;
                        var om = Math.floor(overdue / 60000);
                        timer.innerHTML = '<span class="text-danger"><i class="feather-alert-circle"></i> ' + od + 'd ' + oh + 'h ' + om + 'm overdue</span>';
                    }
                } else if (dataStart) {
                    var start = new Date(dataStart);
                    var elapsed = Math.max(0, now - start);
                    var ed = Math.floor(elapsed / 86400000); elapsed -= ed * 86400000;
                    var eh = Math.floor(elapsed / 3600000); elapsed -= eh * 3600000;
                    var em = Math.floor(elapsed / 60000);
                    timer.innerHTML = '<i class="feather-clock"></i> ' + ed + 'd ' + eh + 'h ' + em + 'm elapsed';
                }
            });
        }

        setInterval(updateProjectTimers, 1000);
        updateProjectTimers();

        if (typeof feather !== 'undefined') feather.replace();
    });

    function viewAttachmentPopup(url) {
        var isImage = /\.(jpeg|jpg|gif|png|webp)$/i.test(url);
        var htmlContent = isImage
            ? '<img src="' + url + '" style="width:100%;max-height:70vh;object-fit:contain;border-radius:8px;">'
            : '<iframe src="' + url + '" style="width:100%;height:70vh;border:none;border-radius:8px;"></iframe>';
        Swal.fire({ title: 'Attachment preview', html: htmlContent, width: '900px', showCloseButton: true, showConfirmButton: false });
    }
</script>
@include('projects.partials.projects-scripts')
