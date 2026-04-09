<table border="1">
    <tr>
        <td colspan="7" style="text-align:center; font-weight:bold; font-size:16px;">
            LAPORAN ZAKAT TAHUN {{ $year }}
        </td>
    </tr>

    <tr>
        <td>Wilayah</td>
        <td colspan="6">: Bandung</td>
    </tr>
    <tr>
        <td>Provinsi</td>
        <td colspan="6">: Jawa Barat</td>
    </tr>
    <tr>
        <td>Tahun</td>
        <td colspan="6">: {{ $year }}</td>
    </tr>

    <tr>
        <td colspan="7"></td>
    </tr>

    <tr style="font-weight:bold; text-align:center;">
        <td>No</td>
        <td>Nama</td>
        <td>Email</td>
        <td>Jenis Zakat</td>
        <td>Metode</td>
        <td>Total Bayar</td>
        <td>Status</td>
        <td>Tanggal</td>
    </tr>

    @foreach($payments as $i => $item)
    <tr>
        <td>{{ $i+1 }}</td>
        <td>{{ $item->user->name ?? '-' }}</td>
        <td>{{ $item->user->email ?? '-' }}</td>
        <td>{{ $item->zakat->type ?? '-' }}</td>
        <td>{{ $item->payment_type }}</td>
        <td>{{ number_format($item->amount,0,',','.') }}</td>
        <td>{{ $item->transaction_status }}</td>
        <td>{{ date('d-m-Y', strtotime($item->created_at)) }}</td>
    </tr>
    @endforeach

    <tr>
        <td colspan="7"></td>
    </tr>

    <tr>
        <td colspan="3" style="text-align:center;">
            Mengetahui<br><br><br><br>
            Admin Zakat
        </td>

        <td colspan="4" style="text-align:center;">
            Bandung, {{ date('d F Y') }}<br><br><br><br>
            Sistem Zakatku
        </td>
    </tr>
</table>