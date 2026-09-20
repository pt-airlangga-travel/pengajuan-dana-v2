<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $judul }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            font-family: "Arial, Helvetica, sans-serif";
            font-size: 14px;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: end;
        }

        .mt-2 {
            margin-top: 2em;
        }

        .mt-3 {
            margin-top: 3em;
        }

        .fs-18 {
            font-size: 18px;
        }

        .fs-24 {
            font-size: 24px;
        }

        .fs-10 {
            font-size: 10px;
        }

        table {
            width: 80%;
            margin: auto;
            border-collapse: collapse;

        }

        table tr td {
            width: auto;
            padding: 5;
            margin: 0;
            /* Menambahkan jarak atas dan bawah 10px */
        }

        .p-0 {
            padding: 0
        }

        .m-0 {
            margin: 0
        }

        .px-3 {
            padding: 0 3em
        }

        .pl-1 {
            padding-left: 1em
        }

        .indent {
            padding-left: 0.5em
        }

        .pr-3 {
            padding-right: 3em
        }

        .text-underline {
            text-decoration: underline;
        }

        .text-overline {
            text-decoration: overline;
        }

        .border-top-solid {
            border-top: 1px solid black'

        }

        .border-solid {
            border: 1px solid black'

        }

        .w-33 {
            width: 33.3333%
        }

        .w-25 {
            width: 25%
        }

        .red {
            background-color: red;
        }

        .black {
            filter: grayscale(100%);
        }

        hr {
            border: none;
            border-bottom: 1px solid black
        }

        .inline-block {
            display: inline-block;
        }

        .mx-auto {
            margin-left: auto;
            margin-right: auto
        }

        .mt-auto {
            margin-top: auto;
        }

        td:nth-child(1) {
            width: 32%
        }

        td:nth-child(2) {
            width: 68%
        }

        .page-break {
            page-break-after: always;
        }
        .mark-box-container{
            display: inline-block;
            position: fixed;
            top: 10;
            left: 10;
        }
        .mark-box{
            display: inline-block;
            width: 20px;
            height: 20px;
            border: none;
        }
    </style>
</head>

<body>
    <div class="mark-box-container">
        @foreach ($colors as $item)
        <div class="mark-box" style="background-color: {{ $item->color }}"></div>
        @endforeach
    </div>
    <h1 class="text-center mt-3 fs-18">FORMULIR PENGAJUAN DANA</h1>
    <p class="text-center ">{{ $proposalSubmission->no_surat }}</p>
    <div class="mt-2">
        <table>
            <tr>
                <td style="width: 32%">
                    <p>Tanggal pengajuan</p>
                </td>
                <td style="width: 68%">
                    <p>: {{ date('d-m-Y', strtotime($proposalSubmission->tanggal_pengajuan)) }}</p>
                </td>
            </tr>
            <tr>
                <td>
                    <p>Divisi yang mengajukan</p>
                </td>
                <td>: {{ $proposalSubmission->divisi_yang_mengajukan }}</td>
            </tr>
            <tr>
                <td>Deadline pembayaran</td>
                <td>: {{ date('d-m-Y', strtotime($proposalSubmission->deadline_pembayaran)) }}</td>
            </tr>
            <tr>
                <td>PIC / Pemohon</td>
                <td>: {{ $proposalSubmission->nama_pemohon }}</td>
            </tr>
            <tr>
                <td>INSTANSI Konsumen</td>
                <td>: {{ $proposalSubmission->instansi_konsumen }} </td>
            </tr>
            <tr>
                <td>Tanggal Pelaksanaan Kegiatan</td>
                @if ($proposalSubmission->tanggal_mulai == $proposalSubmission->tanggal_selesai)
                    <td>: {{ date('d-m-Y', strtotime($proposalSubmission->tanggal_mulai)) }}</td>
                @else
                    <td>: {{ date('d/m/Y', strtotime($proposalSubmission->tanggal_mulai)) }} -
                        {{ date('d/m/Y', strtotime($proposalSubmission->tanggal_selesai)) }}</td>
                @endif
            </tr>
            
            <tr>
                <td>Nama Vendor</td>
                <td class="">: {{ $vendor[0]?->vendor_name }}
                </td>
            </tr>
            <tr>
                <td>
                    Kontak Marketing Vendor
                </td>
                <td>: {{ $vendor[0]?->vendor_contact }} </td>
            </tr>
            <tr>
                <td>
                    Email Vendor
                </td>
                <td>: {{ $vendor[0]?->vendor_email }} </td>
            </tr>

            <tr>
                <td class=" ">Informasi penerima</td>
                <td class="">
                    <div class="inline-block">: Nama Bank</div>
                    <div class="inline-block">: {{ $bankAccount[0]?->bank_name }}</div>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <div class="inline-block indent">
                        No Rekening
                    </div>
                    <div class="inline-block ">
                        : {{ $bankAccount[0]?->bank_account_number }}
                    </div>
                </td>
            </tr>
            <tr class="">
                <td></td>
                <td>
                    <div class="inline-block indent">
                        Atas Nama
                    </div>
                    <div class="inline-block">
                        : {{ $bankAccount[0]?->bank_account_owner }}
                    </div>
                </td>
            <tr class="">
                <td></td>
                <td>
                    <div class="inline-block indent">
                        Dijalankan Tanggal
                    </div>
                    <div class="inline-block">
                        : {{ $bankAccount[0]?->dijalankan_tanggal }}
                    </div>
                </td>
            </tr>
            <tr class="">
                <td></td>
                <td>
                    <div class="inline-block indent">
                        Melalui
                    </div>
                    <div class="inline-block">
                        : {{ $bankAccount[0]?->melalui }} {{ $bankAccount[0]?->id_bank_asal }}
                    </div>
                </td>
            </tr>

            <tr>
                <td>Nominal</td>
                <td>: Rp {{ $nominal }}</td>
            </tr>
            <tr>
                <td>Keperluan</td>
                <td>:
                  {{ $needs->need_name }}
                </td>
            </tr>
            <tr>
                <td class="">Kode Booking <br> <span class="fs-10">* ( Pesawat / Kereta)</span></td>
                <td>: {{ $proposalSubmission->booking_code }} </td>
            </tr>

        </table>
        <table class="">
            <tr>
                <td style="width: 40%"></td>
                <td style="width: 20%"></td>
                <td style="width: 40%">Surabaya, {{ date('d-M-Y', strtotime($proposalSubmission->checked_date)) }} </td>
            </tr>
            <tr>
                <td>Diajukan Oleh</td>
                <td>Diketahui Oleh</td>
                <td>Disetujui Oleh</td>
            </tr>
            <tr>
                <td style="height: 100px"></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="pr-3 p-0 ">
                    <hr>
                </td>
                <td class="pr-3 p-0 ">
                    <hr>
                </td>
                <td class="pr-3 p-0 ">
                    <hr>
                </td>

            </tr>
            <tr>
                <td class="  p-0">PIC/Pemohon</td>
                <td class="  p-0">Manajer Bisnis</td>
                <td class="  p-0">Direktur
                </td>
            </tr>
            <tr>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td>
                    <img src="{{ $qr }}" width="100" alt="">
                </td>
                <td class="text-center " style="vertical-align: bottom;">
                    Bendahara
                </td>
                <td>
                    <img src="{{ $logoAgt }}" width="100" alt="">
                </td>

            </tr>


        </table>
       
    </div>
</body>

</html>
