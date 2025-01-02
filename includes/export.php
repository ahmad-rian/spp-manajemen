<?php
function exportToCSV($payments)
{
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="payments_report.csv"');

    $output = fopen('php://output', 'w');

    // Headers
    fputcsv($output, array(
        'Tanggal',
        'Siswa',
        'Kelas',
        'Periode',
        'Bulan',
        'Jumlah',
        'Status',
        'Kasir'
    ));

    // Data
    foreach ($payments as $payment) {
        fputcsv($output, array(
            date('d/m/Y', strtotime($payment['tanggal_bayar'])),
            $payment['siswa_nama'],
            $payment['kelas'],
            $payment['tahun_ajaran'],
            date('F', mktime(0, 0, 0, $payment['bulan'], 1)),
            'Rp ' . number_format($payment['jumlah_bayar'], 0, ',', '.'),
            $payment['status'],
            $payment['kasir_nama']
        ));
    }

    fclose($output);
    exit;
}

function generateReceipt($payment)
{
    // Simple HTML receipt
    $html = '
    <html>
    <head>
        <style>
            body { font-family: Arial; }
            .container { width: 800px; margin: 0 auto; }
            .header { text-align: center; }
            table { width: 100%; border-collapse: collapse; }
            td, th { padding: 8px; border: 1px solid #ddd; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h2>BUKTI PEMBAYARAN SPP</h2>
                <p>No: SPP-' . sprintf("%05d", $payment['id']) . '</p>
            </div>
            <table>
                <tr>
                    <td>Tanggal</td>
                    <td>' . date('d/m/Y', strtotime($payment['tanggal_bayar'])) . '</td>
                </tr>
                <tr>
                    <td>Siswa</td>
                    <td>' . $payment['siswa_nama'] . '</td>
                </tr>
                <tr>
                    <td>Kelas</td>
                    <td>' . $payment['kelas'] . '</td>
                </tr>
                <tr>
                    <td>Periode</td>
                    <td>' . $payment['tahun_ajaran'] . '</td>
                </tr>
                <tr>
                    <td>Bulan</td>
                    <td>' . date('F', mktime(0, 0, 0, $payment['bulan'], 1)) . '</td>
                </tr>
                <tr>
                    <td>Jumlah</td>
                    <td>Rp ' . number_format($payment['jumlah_bayar'], 0, ',', '.') . '</td>
                </tr>
                <tr>
                    <td>Status</td>
                    <td>' . ucfirst($payment['status']) . '</td>
                </tr>
                <tr>
                    <td>Kasir</td>
                    <td>' . $payment['kasir_nama'] . '</td>
                </tr>
            </table>
            <div class="footer" style="margin-top: 50px; text-align: right;">
                <p>Petugas</p>
                <br><br>
                <p>' . $payment['kasir_nama'] . '</p>
            </div>
        </div>
    </body>
    </html>';

    return $html;
}
