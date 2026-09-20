<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
    
    // value:int ,digit:int
    protected function defined_id($value, $digits) : string {
        $value = (string) $value;
        $padding = $digits - strlen($value);
        if ($padding > 0) {
            $value = str_repeat('0', $padding) . $value;
        }
        return $value;
    }
    protected function formatDefinedId($data) {
        if (strlen($data>12)) {
            $formattedData = substr($data, 0, 4) . '.' . substr($data, 4, 2) . '.' . substr($data, 6, 3) . '.' . substr($data, 9, 3) . '.' . substr($data, 12, 3) . '.' . substr($data, 15, 2);
        }else{
            $formattedData = substr($data, 0, 4) . '.' . substr($data, 4, 2) . '.' . substr($data, 6, 3) . '.' . substr($data, 9, 3);
        }
        return $formattedData;
    }
    protected function createSlug($string) {
        $string = preg_replace('/[^a-zA-Z0-9\s]/', '', $string);
        $string = str_replace(' ', '-', $string);
        $string = strtolower($string);
        return $string;
    }

    protected function getFileAsBase64(String $filePath)
    {
        $data = file_get_contents($filePath);
        $type = pathinfo($filePath, PATHINFO_EXTENSION);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
    protected function terbilang($angka)
    {
        $angka = floatval($angka);
        $bilangan = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];
        if ($angka < 12) {
            return $bilangan[$angka];
        } elseif ($angka < 20) {
            return $bilangan[$angka - 10] . ' belas';
        } elseif ($angka < 100) {
            $hasil_bagi = (int) ($angka / 10);
            $hasil_mod = $angka % 10;
            return trim($bilangan[$hasil_bagi] . ' puluh ' . $bilangan[$hasil_mod]);
        } elseif ($angka < 200) {
            return 'seratus ' . $this->terbilang($angka - 100);
        } elseif ($angka < 1000) {
            $hasil_bagi = (int) ($angka / 100);
            $hasil_mod = $angka % 100;
            return trim($bilangan[$hasil_bagi] . ' ratus ' . $this->terbilang($hasil_mod));
        } elseif ($angka < 2000) {
            return 'seribu ' . $this->terbilang($angka - 1000);
        } elseif ($angka < 1000000) {
            $hasil_bagi = (int) ($angka / 1000);
            $hasil_mod = $angka % 1000;
            return trim($this->terbilang($hasil_bagi) . ' ribu ' . $this->terbilang($hasil_mod));
        } elseif ($angka < 1000000000) {
            $hasil_bagi = (int) ($angka / 1000000);
            $hasil_mod = $angka % 1000000;
            return trim($this->terbilang($hasil_bagi) . ' juta ' . $this->terbilang($hasil_mod));
        } elseif ($angka < 1000000000000) {
            $hasil_bagi = (int) ($angka / 1000000000);
            $hasil_mod = fmod($angka, 1000000000);
            return trim($this->terbilang($hasil_bagi) . ' milyar ' . $this->terbilang($hasil_mod));
        } elseif ($angka < 1000000000000000) {
            $hasil_bagi = (int) ($angka / 1000000000000);
            $hasil_mod = fmod($angka, 1000000000000);
            return trim($this->terbilang($hasil_bagi) . ' triliun ' . $this->terbilang($hasil_mod));
        } else {
            return 'angka';
        }
    }
    protected function proposalformvalidation(Request $request){
        $id= $request->id;
        $judul = "Formulir Pengajuan Dana";
        $proposalSubmission = DB::table('proposal_submissions as ps')
        ->join('proposal_drafts as pd','pd.proposal_draft_defined_id','=','ps.id_proposal_draft')
        ->join('events as e','e.event_defined_id','=','pd.id_event')
        ->join('users as u','pd.creative_member','=','u.id')
        ->join('divisions as d','d.division_defined_id','=','u.id_division')
        ->join('institutions as i','i.institution_defined_id','=','e.id_institution')
        ->select(
            'pd.proposal_draft_defined_id as id_draft',
            'pd.created_at as tanggal_pengajuan',
            'ps.proposal_submission_event_identity as no_surat',
            'pd.proposal_draft_deadline_payment as deadline_pembayaran',
            'ps.proposal_submission_booking_code as booking_code',
            'pd.creative_member',
            'd.division_name as divisi_yang_mengajukan',
            'u.name as nama_pemohon',
            'i.institution_name as instansi_konsumen',
            'e.event_started_at as tanggal_mulai',
            'e.event_finished_at as tanggal_selesai',
            )
        ->where('ps.proposal_submission_defined_id',$id)  
        ->first();
    
        $vendors = DB::table('vendors')
        ->select(['vendor_name','vendor_contact','vendor_email','vendor_sub_total'])
        ->where('id_proposal_draft', $proposalSubmission->id_draft)
        ->get();
        $needs= DB::table('need_submissions as ns')
        ->join('needs as n','ns.id_need','=','n.need_defined_id')
        ->select('n.need_name')
        ->where('ns.id_proposal_submission',$id)
        ->get();
        $bankAccounts= DB::table('bank_accounts as ba')
        ->join('bank_transfers as bt','bt.id_bank_account','=','ba.id')
        ->join('banks as b','b.bank_defined_id','ba.id_bank')
        ->join('users as u','u.id','=','bt.eagle_treasurer')
        ->select('b.bank_name','ba.bank_account_number','ba.bank_account_owner','bt.bank_transfer_melalui as melalui','bt.bank_transfer_dijalankan_tanggal as dijalankan_tanggal','u.name as bendahara')
        ->where('ba.id_proposal_submission',$id)
        ->get();
        return view('proposalformvalidation', compact('id', 'judul', 'proposalSubmission', 'vendors', 'needs', 'bankAccounts'));

    }
    public function printFormulir(Request $request)
    {
        $id= $request->id;
        $judul = "Formulir Pengajuan Dana-$id";
        $proposalSubmission = DB::table('proposal_submissions as ps')
        ->join('proposal_drafts as pd','pd.proposal_draft_defined_id','=','ps.id_proposal_draft')
        ->join('events as e','e.event_defined_id','=','pd.id_event')
        ->join('users as u','pd.creative_member','=','u.id')
        ->join('divisions as d','d.division_defined_id','=','u.id_division')
        ->join('institutions as i','i.institution_defined_id','=','e.id_institution')
        ->select(
            'pd.proposal_draft_defined_id as id_draft',
            'pd.created_at as tanggal_pengajuan',
            'ps.proposal_submission_event_identity as no_surat',
            'pd.proposal_draft_deadline_payment as deadline_pembayaran',
            'ps.proposal_submission_booking_code as booking_code',
            'ps.checked_date as checked_date',
            'pd.creative_member',
            'd.division_name as divisi_yang_mengajukan',
            'u.name as nama_pemohon',
            'i.institution_name as instansi_konsumen',
            'e.event_started_at as tanggal_mulai',
            'e.event_finished_at as tanggal_selesai',
            )
        ->where('ps.proposal_submission_defined_id',$id)  
        ->first();
        
        $vendor = Vendor::where('id_proposal_draft',$proposalSubmission->id_draft)->get();
        $nominal =0;
        foreach ($vendor as $item) {
            $subTotal = str_replace('.', '', $item->vendor_sub_total);
            $nominal += $subTotal;
        }
        $nominal = number_format($nominal, 0, ',', '.');
        $terbilang = $this->terbilang(str_replace('.','',$nominal))." rupiah";
        $needs= DB::table('need_submissions as ns')
        ->join('needs as n','ns.id_need','=','n.need_defined_id')
        ->select('n.need_name')
        ->where('ns.id_proposal_submission',$id)
        ->first();
        $bankAccount= DB::table('bank_accounts as ba')
        ->join('bank_transfers as bt','bt.id_bank_account','=','ba.id')
        ->join('banks as b','b.bank_defined_id','ba.id_bank')
        ->leftjoin('bank_asals as b2','b2.id','bt.id_bank_asal')
        ->join('users as u','u.id','=','bt.eagle_treasurer')
        ->select('b.bank_name','ba.bank_account_number','ba.bank_account_owner','bt.bank_transfer_melalui as melalui','bt.bank_transfer_dijalankan_tanggal as dijalankan_tanggal','u.name as bendahara','b2.bank_name as id_bank_asal','ba.bank_account_sub_total','b2.no_rekening')
        ->where('ba.id_proposal_submission',$id)
        ->get();
        $colors= DB::table('bank_accounts as ba')
        ->join('bank_transfers as bt','bt.id_bank_account','=','ba.id')
        ->leftjoin('bank_asals as b','b.id','bt.id_bank_asal')
        ->select('b.color')
        ->groupBy('b.color')
        ->where('ba.id_proposal_submission',$id)
        ->get();
        foreach ($colors as $item) {
            if ($item->color==null) {
                $item->color="black";
            }
        }
        $logoAgt = $this->getFileAsBase64('./logo-agt.png');
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class) && class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            $qrCodes =  \SimpleSoftwareIO\QrCode\Facades\QrCode::size(150)->generate(route('proposalformvalidation',['id'=>$request->id]));
            $qr = "data:image/svg+xml;base64,".base64_encode($qrCodes);
            if(strtolower($needs->need_name)=='fee'){
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('formulir-fee',compact('judul','proposalSubmission','vendor','needs','logoAgt','qr','bankAccount','terbilang','nominal','colors'));
            }else{
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('formulir-non-fee',compact('judul','proposalSubmission','vendor','needs','logoAgt','qr','bankAccount','terbilang','nominal','colors'));
            }
            return $pdf->stream("formulir-$id.pdf");
        }
        abort(500, 'PDF/QR dependencies not installed.');
    }
    public function exportExcel(Request $request) 
    {
        if (class_exists(\Maatwebsite\Excel\Facades\Excel::class) && class_exists(\App\Exports\submissionExport::class)) {
            return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\submissionExport($request->status), 'data.xlsx');
        }
        abort(500, 'Excel dependency not installed.');
    }
   
}
