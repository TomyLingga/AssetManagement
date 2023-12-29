<html>
<head>
	<title>Form Request No. {{$fr->no_wo}}</title>
	<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<style type="text/css">
	.under{
		text-decoration: underline;
	}
	.font{
		font-family:sans-serif;
		font-size: 14px;
        text-decoration: underline;
		color: black;
        margin-top:0px;
	}
	.bold{
		font-weight: bold;
	}
    .tbl{
        border: 1px solid black;
        width: 100%;
    }
    .tdr{
        border: 1px;
        border-color: black;
        border-right-style: solid;
    }
    .tdl{
        border: 1px;
        border-color: black;
        border-left-style: solid;
    }
    .tdl2{
        border: 1px;
        border-color: black;
        border-left-style: solid;
        border-top-style: solid;
        margin-top: 2px;
        /* text-align: center; */
    }
    .tbl2{
        border: 4px;
        width: 100%;
        /* padding: 10px; */
        border-style: solid;
    }
    .tbl3{
        width: 99%;
        /* padding: 11px; */
        margin:7px;
    }
    .tr{
        border: 2px;
        width: 100%;
        /* padding: 0; */
        border-style: solid;
        margin:2px;
    }
    .tr2{
        border:1px;
        border-style: solid;
        padding: 5px;
    }
    .tx-center{
        text-align:center;
    }
    .ft2{
        font-size: 11px;
        color: black;
		font-family:sans-serif;
        margin: 0;
    }
    .ft3{
        font-family:sans-serif;
		font-size: 13px;
        /* text-decoration: underline; */
		color: black;
        /* margin-top:20px; */
    }
    .border{
        border: 3px solid black;
        /* width:100%; */
    }
</style>
<body style="margin:0px;">
    <div class="">
    <table class="tbl" cellspacing="0" cellpadding="0">
			<tr>
				<td class="tdr tx-center" width="13%">
					<img src="storage/surat/inl.png" style="margin-top:25;" width="85">
				</td>
                <td class="tx-center" width="">
                    <b class="font">PT. INDUSTRI NABATI LESTARI</b>
                    <p class="ft2"><b >PABRIK MINYAK GORENG</b></p>
                    <p class="ft2"><b> Kantor Pusat : Komp. KEK Sei Mangkei, Kav.2-3, Kec. Bosar Maligas,</b></p>
                    <p class="ft2"><b> Kab. Simalungun,</b></p>
                    <p class="ft2"><b> Sumatera Utara, 21184</b></p>
                </td>
				<td class="tdl tx-center" style="position: absolute; top: 50%; font-size:11px;" width="18%">
                    <b>No. Dokumen</b>
					<p>INLHO/BSIS-ITC/F-004</p>
                </td>
				<td class="tdl tx-center" style="position: absolute; top: 50%; font-size:11px;" width="15%">
                    <b>Tgl. Berlaku</b>
					<p>04-Mei-22</p>
                </td>
			</tr>
            <tr>
				<td class="tdr">

				</td>
                <td class="tx-center">

                    <b class="ft3">FORM REQUEST</b>
					<p class="ft2">{{$fr->no_wo}}</p>
                </td>
                <td class="tdl2 tx-center" style="position: absolute; top: 50%; font-size:11px;">
                    <b>No. Revisi</b>
                    <p>01</p>
                </td>
				<td class="tdl2 tx-center" style=" position: absolute; top: 50%; font-size:11px;">
                    <b>Halaman</b>
                    <p>1 dari 1</p>
                </td>
			</tr>
		</table>
        <table class="tbl" style="font-size:11px; margin-top:3px;">
            <tr>
                <td>
                    <table class="tbl2">
                        <tr>
                            <td>
                                <table class="tbl3">
                                    <tr class="">
                                        <td class="">
                                            <div ><b>Office: </b><span>{{$fr->office}}</span></div>
                                        </td>
                                        <td class="">

                                        </td>
                                    </tr>
                                    <tr class="" style="background-color:#58D68D;">
                                        <td class="tr2" width="45%">
                                            <b>KEPERLUAN</b>
                                        </td>
                                        <td class="tr2 tx-center">
                                            <b>PENGESAHAN</b>
                                        </td>
                                    </tr>
                                    <tr class="" style="border:2px; border-bottom-style:solid">
                                        <td class="" style="padding-left:10px;">
                                            <span>{{$fr->keperluan}}</span>
                                        </td>
                                        <td class="" style="padding-left:5px; border: 1px; border-left-style:solid;">
                                            <table width="100%">
                                                <tr>
                                                    <td width="35%">
                                                        <b>YANG BERSANGKUTAN</b>
                                                    </td>
                                                    <td>
                                                        <span>: Diminta oleh {{$fr->user->name ?? '.....'}} pada tanggal {{ $fr->approve_user ? \Carbon\Carbon::parse($fr->approve_user)->locale('id')->isoFormat('D MMMM YYYY', 'Do MMMM YYYY') : '.....' }}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <b>NO. HP</b>
                                                    </td>
                                                    <td>
                                                        <span>: {{$fr->hp_a ?? '.....'}}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <b>ATASAN YBS</b>
                                                    </td>
                                                    <td>
                                                        <span>: Disetujui oleh {{$fr->atasan->name ?? '.....'}} pada tanggal {{ $fr->approve_atasan ? \Carbon\Carbon::parse($fr->approve_atasan)->locale('id')->isoFormat('D MMMM YYYY', 'Do MMMM YYYY') : '.....' }}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td style="border:1px; border-bottom-style:solid">
                                                        <b>NO. HP</b>
                                                    </td>
                                                    <td style="border:1px; border-bottom-style:solid">
                                                        <span>: {{$fr->hp_b ?? '.....'}}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <b>MANAGER/OFFICER DIVISI</b>
                                                    </td>
                                                    <td>
                                                        <span>: Disetujui oleh {{$fr->deptPic->name ?? '.....'}} pada tanggal {{ $fr->approve_kategori_mgr ? \Carbon\Carbon::parse($fr->approve_kategori_mgr)->locale('id')->isoFormat('D MMMM YYYY', 'Do MMMM YYYY') : '.....' }}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <b>NO. HP</b>
                                                    </td>
                                                    <td>
                                                        <span>: {{$fr->hp_c ?? '.....'}}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <b>EXECUTOR</b>
                                                    </td>
                                                    <td>
                                                        <span>: {{ $fr->executor ? 'Diselesaikan oleh ' . $fr->executor->name . ' pada tanggal ' . \Carbon\Carbon::parse($fr->approve_kategori_fr)->locale('id')->isoFormat('D MMMM YYYY', 'Do MMMM YYYY') : '.....' }}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <b>NO. HP</b>
                                                    </td>
                                                    <td>
                                                        <span>: {{$fr->hp_d ?? '.....'}}</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <tr class="">
                                        <td class="" style="padding-left:10px; background-color:#58D68D; border:1px solid;">
                                            <b>JENIS PERMINTAAN</b>
                                        </td>
                                        <td style="padding:10px; background-color:#58D68D; border:1px solid;">
                                            <b>PETUNJUK DAN ATURAN</b>
                                        </td>
                                    </tr>
                                    <tr class="">
                                        <td style="padding-left:5px;">
                                            <span>{{$fr->category->nama_kategori}} / {{$fr->category->nama_permintaan}}</span>
                                        </td>
                                        <td style="padding-left:5px; font-style:italic;">
                                            <p style="margin-bottom:0px;">1. Bahwa dengan pengesahan diatas, yang bersangkutan telah menerima IT Security Policy INL</p>
                                            <p style="margin-bottom:0px;">2. Password akan diinformasikan langsung oleh administrator via Aplikasi/HP/SMS/Extention</p>
                                            <p style="margin-bottom:0px;">3. Penggunaan akses menjadi tanggung jawab perseorangan. Tanggung jawab meliputi kerahasiaan informasi dan menjaga kerahasiaan password</p>
                                        </td>
                                    </tr>
                                    <tr class="">
                                        <td class="" style="padding:10px; background-color:#58D68D; border:1px solid;">
                                            <b>PRIORITAS</b>
                                        </td>
                                        <td style="padding:10px; background-color:#58D68D; border:1px solid;">
                                            <b>KETERANGAN</b>
                                        </td>
                                    </tr>
                                    @php
                                        function mapPrioritas($prioritas) {
                                            switch ($prioritas) {
                                                case 1:
                                                    return 'Rendah';
                                                case 2:
                                                    return 'Menengah';
                                                case 3:
                                                    return 'Tinggi';
                                                default:
                                                    return '...'; // Or any default value you prefer
                                            }
                                        }
                                    @endphp
                                    <tr class="">
                                        <td class="" style="padding-left: 10px;">
                                            <p>{{ mapPrioritas($fr->prioritas) }}</p>
                                        </td>
                                        <td style="padding-left:10px; font-style:italic;">
                                            <p>{{$fr->keterangan ?? '.....'}}</p>
                                        </td>
                                    </tr>
                                    <tr class="">
                                        <td class="" style="padding:10px; background-color:#58D68D; border:1px solid;">
                                            <b>IDENTITAS KARYAWAN</b>
                                        </td>
                                        <td></td>
                                    </tr>
                                    <tr class="">
                                        <td class="" style="padding-left:10px;">
                                            <table>
                                                <tr>
                                                    <td>
                                                        <span>NAMA LENGKAP</span>
                                                    </td>
                                                    <td>
                                                        <span>: {{$fr->user->name}}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span>STATUS KARYAWAN</span>
                                                    </td>
                                                    <td>
                                                        <span>: {{$fr->user->status_karyawan}}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span>NRK</span>
                                                    </td>
                                                    <td>
                                                        <span>: {{$fr->nrk}}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span>JABATAN</span>
                                                    </td>
                                                    <td>
                                                        <span>: {{$fr->user->jabatan}}</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                        <td>
                                        <table>
                                                <tr>
                                                    <td>
                                                        <span>ATASAN</span>
                                                    </td>
                                                    <td>
                                                        <span>: {{$fr->atasan->name}}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span>DIVISI</span>
                                                    </td>
                                                    <td>
                                                        <span>: {{$userResult['division']['divisi']}}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span>DEPARTEMEN</span>
                                                    </td>
                                                    <td>
                                                        <span>: {{$userResult['department']['department']}}</span>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                        <span>EMAIL INL</span>
                                                    </td>
                                                    <td>
                                                        <span>: {{$fr->email_inl}}</span>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table style="width:100%;">
            <tr style="">
                <td>
                    <b style="font-size:10px;">Untuk informasi, silakan menghubungi IT <span style="font-style: italic; font-size:10px;">HP : 081260666418 Ext. 144</span></b>
                </td>
                <td>
                    <span style="font-size:10px; font-style: italic;">(versi dokumen : 1/Agustus 2019)</span>
                </td>
            </tr>
        </table>
        <!-- <div class="row">
            <div class="col-6">
                <b style="font-size:10px;">Untuk informasi, silakan menghubungi IT <span style="font-style: italic; font-size:10px;">HP : 081260666418 Ext. 144</span></b>
            </div>
            <div class="col-6">
            </div>
        </div> -->
	</div>
</body>
</html>
