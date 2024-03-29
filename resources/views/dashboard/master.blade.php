@extends('dashboard.layout.app')

@section('content')

@if (Session::has('success'))
<script>
    Swal.fire({
        icon: 'success',
        title: '{{ Session::get("success") }}',
    });
</script>
@endif


<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 ml-2"> Hello, <small>{{ $name }}</small></h1>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="content">
                <div class="container-fluid">
                    <form action="{{ route('dashboard') }}" method="GET">
                        @csrf
                        <div class="row">
                            <div class="col-md-9">
                                <div class="card w-100">
                                    <div class="card-header mt-2">
                                        <div class="row">
                                            <div class="col-md-9 text-left">
                                                <label><i class="fas fa-chart-column"></i> Grafik Tamu (Bulan)</label>
                                            </div>
                                            <div class="col-md-3 text-right">
                                                <select name="tahun" class="form-control form-control-sm w-100" onchange="this.form.submit()">
                                                    @foreach(range(2024, 2030) as $yearNumber)
                                                    @php $rowTahun = Carbon\Carbon::create()->year($yearNumber)->isoFormat('Y'); @endphp
                                                    <option value="{{ $rowTahun }}" <?php echo $tahun == $rowTahun ? 'selected' : ''; ?>>
                                                        {{ $rowTahun }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-header">
                                        <div class="row">
                                            <div class="col-md-7">
                                                <div class="chart" style="height: 50vh;">
                                                    <canvas id="monthChart"></canvas>
                                                </div>
                                            </div>
                                            <div class="col-md-5">
                                                <label><i class="fas fa-table"></i> Rekapitulasi Total</label>
                                                <div class="row ml-0">
                                                    @foreach(range(1,12) as $row)
                                                    @php $rowBulan = Carbon\Carbon::create()->month($row); @endphp
                                                    <div class="col-md-3 text-center border border-dark pt-2">
                                                        <h6 class="small" id="monthName-{{ $row }}">{{ $rowBulan->isoFormat('MMMM') }}</h6>
                                                        <label class="h6" id="totalVisit-{{ $row }}">0</label>
                                                    </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card w-100">
                                    <div class="card-header mt-2">
                                        <div class="row">
                                            <div class="col-md-7 text-left">
                                                <label><i class="fas fa-chart-line"></i> Grafik Tamu (Hari)</label>
                                            </div>
                                            <div class="col-md-3 text-right">
                                                <select name="bulan" class="form-control form-control-sm" onchange="this.form.submit()">
                                                    @foreach(range(1, 12) as $monthNumber)
                                                    @php $rowBulan = Carbon\Carbon::create()->month($monthNumber); @endphp
                                                    <option value="{{ $rowBulan->isoFormat('MM') }}" <?php echo $bulan == $rowBulan->isoFormat('MM') ? 'selected' : ''; ?>>
                                                        {{ $rowBulan->isoFormat('MMMM') }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2 text-right">
                                                <select name="tahunBulan" class="form-control form-control-sm w-100" onchange="this.form.submit()">
                                                    @foreach(range(2024, 2030) as $yearNumber)
                                                    @php $rowTahun = Carbon\Carbon::create()->year($yearNumber)->isoFormat('Y'); @endphp
                                                    <option value="{{ $rowTahun }}" <?php echo $tahunBulan == $rowTahun ? 'selected' : ''; ?>>
                                                        {{ $rowTahun }}
                                                    </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-header">
                                        <div class="chart">
                                            <canvas id="dayChart" style="height: 320px;"></canvas>
                                        </div>
                                    </div>
                                    <div class="card-header">
                                        <table id="table-day" class="table table-striped text-sm text-center">
                                            <thead>
                                                <tr>
                                                    <th>No</th>
                                                    <th>Tanggal</th>
                                                    <th>Total</th>
                                                </tr>
                                            </thead>
                                            <tbody id="table-body"></tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <div class="card">
                                        <div class="card-header">
                                            <div class="row text-sm">
                                                <div class="col-md-12">
                                                    <label><i class="fas fa-calendar"></i> Tanggal</label>
                                                    <h6 id="timestamp"></h6>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

@section('js')
<script>
    //-------------------
    //- BAR CHART BULAN -
    //-------------------
    var url = "{{ route('tamu.chart', ['id' => 'bulan', 'bulan' => '*', 'tahun' => $tahun]) }}"
    var Month = new Array();
    var TotalVisit = new Array();
    $(document).ready(function() {
        $.get(url, function(response) {
            response.forEach(function(data) {
                var rawDate = new Date(data.month);
                var formattedDate = rawDate.toLocaleDateString('id-ID', {
                    month: 'long',
                    year: 'numeric'
                });
                Month.push(formattedDate);
                TotalVisit.push(data.total_tamu);
            });

            var barChartCanvas = $('#monthChart').get(0).getContext('2d')
            var barChartData = {
                labels: Month,
                datasets: [{
                    label: 'Total Tamu',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgb(54, 162, 235)',
                    borderWidth: 1,
                    pointRadius: false,
                    pointColor: '#3b8bba',
                    pointStrokeColor: 'rgba(60,141,188,1)',
                    pointHighlightFill: '#fff',
                    pointHighlightStroke: 'rgba(60,141,188,1)',
                    data: TotalVisit
                }]
            }
            var temp0 = barChartData.datasets[0]
            barChartData.datasets[0] = temp0

            var barChartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }

            new Chart(barChartCanvas, {
                type: 'bar',
                data: barChartData,
                options: barChartOptions
            })

            var month = document.getElementById('monthName');
            var total = document.getElementById('totalVisit');
            response.forEach(function(data, index) {
                var rawDate = new Date(data.month);
                var monthNumber = rawDate.getMonth() + 1; // Mendapatkan nomor bulan dari data
                var month = document.getElementById('monthName-' + monthNumber);
                var total = document.getElementById('totalVisit-' + monthNumber);
                var monthName = rawDate.toLocaleDateString('id-ID', {
                    month: 'long'
                });
                month.innerHTML = monthName;
                total.innerHTML = data.total_tamu;
            });
        });
    });
</script>

<script>
    //-------------------
    //- BAR CHART HARI -
    //-------------------
    var dayUrl = "{{ route('tamu.chart', ['id' => 'hari', 'bulan' => $bulan, 'tahun' => $tahunBulan]) }}"
    var Day = new Array();
    var DayTotalVisit = new Array();
    $(document).ready(function() {
        $.get(dayUrl, function(result) {
            result.forEach(function(data) {
                Day.push(data.month);
                DayTotalVisit.push(data.total_tamu);
            });

            var lineChartCanvas = $('#dayChart').get(0).getContext('2d')
            var lineChartData = {
                labels: Day,
                datasets: [{
                    label: 'Total Tamu',
                    backgroundColor: 'rgba(255, 0, 35, 0.2)',
                    borderColor: 'rgb(255, 0, 35)',
                    borderWidth: 1,
                    pointRadius: false,
                    pointColor: '#3b8bba',
                    pointStrokeColor: 'rgba(60,141,188,1)',
                    pointHighlightFill: '#fff',
                    pointHighlightStroke: 'rgba(60,141,188,1)',
                    data: DayTotalVisit
                }]
            }
            var temp0 = lineChartData.datasets[0]
            lineChartData.datasets[0] = temp0

            var lineChartOptions = {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    yAxes: [{
                        ticks: {
                            beginAtZero: true
                        }
                    }]
                }
            }

            new Chart(lineChartCanvas, {
                type: 'line',
                data: lineChartData,
                options: lineChartOptions
            })

            var table = $('#table-day').DataTable();

            result.forEach(function(data, index) {
                table.row.add([index + 1, data.month, data.total_tamu]).draw();
            });
        });
    });
</script>
@endsection
@endsection
