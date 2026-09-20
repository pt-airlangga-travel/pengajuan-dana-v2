<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      margin: 0;
      padding: 0;
      font-family: "Tahoma", sans-serif;
    }

    .page {
      width: 100%;
      max-width: 800px;
      margin: 0 auto;
      padding: 15mm;
      border: 1px #D3D3D3 solid;
      border-radius: 5px;
      background: white;
      box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
      position: relative;
    }

    .book {
      padding: 1cm;
    }

    .table-container {
      overflow-x: auto;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    th, td {
      border: 1px solid #dddddd;
      padding: 8px;
      text-align: left;
    }

    th {
      background-color: #f2f2f2;
    }

    .signature-line {
      border-top: 1px solid #000;
      margin-top: 20px;
      width: 100%;
    }

    .signature-block {
      margin-top: 20px;
      text-align: center;
      float: left; 
      width: 25%; 
    }

    ul {
      padding: 0;
      margin: 0;
      list-style-type: none;
    }

    ul li {
      margin-bottom: 5px;
    }

    ul li:last-child {
      margin-bottom: 0;
    }

    .signature-line1 {
      clear: both;
      border-top: 1px solid #000;
      margin-top: 60px;
    }

    @media print {
      body {
        width: 210mm;
        height: 297mm;
      }
      .page {
        margin: 0;
        border: initial;
        border-radius: initial;
        box-shadow: initial;
        background: initial;
        page-break-after: always;
      }
    }
  </style>
</head>
<body>
  <div class="book">
    <div class="page">
      <h1 style="text-align: center;">Formulir Pengajuan Dana</h1>
      <h3 style="text-align: center;">{{ $proposalSubmission->no_surat }}</h3>
      <p style="margin-top: 50px; margin-bottom: 30px;"><h3 class="email-section-title">Informasi Pengajuan Dana :</h3></p>
      <div class="table-container">
        <table>
          <tr>
            <th style="width: 250px;">Id Pengajuan</th>
            <td>:</td>
            <td>{{ $proposalSubmission->id_draft }}</td>
          </tr>
          <tr>
            <th>Tanggal Pengajuan</th>
            <td>:</td>
            <td>{{ $proposalSubmission->tanggal_pengajuan }}</td>
          </tr>
          <tr>
            <th>Divisi yang mengajukan</th>
            <td>:</td>
            <td>{{ $proposalSubmission->divisi_yang_mengajukan }}</td>
          </tr>
          <tr>
            <th>PIC / Pemohon</th>
            <td>:</td>
            <td>{{ $proposalSubmission->nama_pemohon }}</td>
          </tr>
          <tr>
            <th>INSTANSI Konsumen</th>
            <td>:</td>
            <td>{{ $proposalSubmission->instansi_konsumen }}</td>
          </tr>
          <tr>
            <th>Tanggal Pelaksanaan</th>
            <td>:</td>
            <td>{{ $proposalSubmission->tanggal_mulai }} - {{ $proposalSubmission->tanggal_selesai }}</td>
          </tr>
          <tr>
              <th>Daftar Vendor</th>
              <td>:</td>
              <td>
                  <ul>
                      @foreach ($vendors as $vendor)
                          <li>Nama: {{ $vendor->vendor_name }}</li>
                          <li>Nominal: Rp{{ $vendor->vendor_sub_total }}</li>
                          <li>Kontak: {{ $vendor->vendor_contact }}</li>
                          <li>Email: {{ $vendor->vendor_email }}</li>
                          <br>
                      @endforeach
                  </ul>
              </td>
          </tr>

          <tr>
            <th>Kebutuhan Dana</th>
            <td>:</td>
            <td>
              <ul>
                @foreach ($needs as $need)
                  <li>{{ $need->need_name }}</li>
                @endforeach
              </ul>
            </td>
          </tr>
          <tr>
              <th>Informasi Rekening Bank</th>
              <td>:</td>
              <td>
                  <ul>
                      @foreach ($bankAccounts as $bankAccount)
                          <li>Nama Bank: {{ $bankAccount->bank_name }}</li>
                          <li>Nomor Rekening: {{ $bankAccount->bank_account_number }}</li>
                          <li>Pemilik Rekening: {{ $bankAccount->bank_account_owner }}</li>
                          <li>Metode Pembayaran: {{ $bankAccount->melalui }}</li>
                          <li>Tanggal Dijalankan: {{ $bankAccount->dijalankan_tanggal }}</li>
                          <br> 
                      @endforeach
                  </ul>
              </td>
          </tr>

        </table>

        <p style="margin-top: 50px; margin-bottom: 50px; text-align: right;">Surabaya,_________________</p>
        
        <div class="signature-block">
          <p>Diajukan Oleh :</p>
          <div class="signature-line1" style="width: 180px; margin-top:100px"></div>
          <p>PIC / Pemohon</p>
        </div>
        <div class="signature-block">
          <p>Diketahui Oleh :</p>
          <div class="signature-line1" style="width: 180px; margin-top:100px"></div>
          <p>Manajer Bisnis</p>
        </div>
        <div class="signature-block">
          <p>Disetujui Oleh :</p>
          <div class="signature-line1" style="width: 180px; margin-top:100px"></div>
          <p>Direktur</p>
        </div>
        <div class="signature-block">
          <div class="signature-line1" style="width: 180px; margin-top:135px"></div>
          <p>Bendahara</p>
        </div>
      </div>
      
    </div>
  </div>
</body>
</html>
