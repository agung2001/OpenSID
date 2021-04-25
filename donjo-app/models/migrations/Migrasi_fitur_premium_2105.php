<?php

/**
 * File ini:
 *
 * Model untuk modul database
 *
 * donjo-app/models/migrations/Migrasi_fitur_premium_2105.php
 *
 */

/**
 *
 * File ini bagian dari:
 *
 * OpenSID
 *
 * Sistem informasi desa sumber terbuka untuk memajukan desa
 *
 * Aplikasi dan source code ini dirilis berdasarkan lisensi GPL V3
 *
 * Hak Cipta 2009 - 2015 Combine Resource Institution (http://lumbungkomunitas.net/)
 * Hak Cipta 2016 - 2020 Perkumpulan Desa Digital Terbuka (https://opendesa.id)
 *
 * Dengan ini diberikan izin, secara gratis, kepada siapa pun yang mendapatkan salinan
 * dari perangkat lunak ini dan file dokumentasi terkait ("Aplikasi Ini"), untuk diperlakukan
 * tanpa batasan, termasuk hak untuk menggunakan, menyalin, mengubah dan/atau mendistribusikan,
 * asal tunduk pada syarat berikut:

 * Pemberitahuan hak cipta di atas dan pemberitahuan izin ini harus disertakan dalam
 * setiap salinan atau bagian penting Aplikasi Ini. Barang siapa yang menghapus atau menghilangkan
 * pemberitahuan ini melanggar ketentuan lisensi Aplikasi Ini.

 * PERANGKAT LUNAK INI DISEDIAKAN "SEBAGAIMANA ADANYA", TANPA JAMINAN APA PUN, BAIK TERSURAT MAUPUN
 * TERSIRAT. PENULIS ATAU PEMEGANG HAK CIPTA SAMA SEKALI TIDAK BERTANGGUNG JAWAB ATAS KLAIM, KERUSAKAN ATAU
 * KEWAJIBAN APAPUN ATAS PENGGUNAAN ATAU LAINNYA TERKAIT APLIKASI INI.
 *
 * @package	OpenSID
 * @author	Tim Pengembang OpenDesa
 * @copyright	Hak Cipta 2009 - 2015 Combine Resource Institution (http://lumbungkomunitas.net/)
 * @copyright	Hak Cipta 2016 - 2020 Perkumpulan Desa Digital Terbuka (https://opendesa.id)
 * @license	http://www.gnu.org/licenses/gpl.html	GPL V3
 * @link 	https://github.com/OpenSID/OpenSID
 */

class Migrasi_fitur_premium_2105 extends MY_model {

	public function up()
	{
		log_message('error', 'Jalankan ' . get_class($this));
		$hasil = true;

		// Ubah kolom supaya ada nilai default
		$fields = [
			'kartu_tempat_lahir' => ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false, 'default' => ''],
			'kartu_alamat' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => false, 'default' => ''],
		];
		// Ubah keterangan setting aplikasi
		$hasil = $hasil && $this->db->where('key', 'google_key')->update('setting_aplikasi', ['key' => 'mapbox_key', 'keterangan' => 'Mapbox API Key untuk peta']);

		$hasil = $hasil && $this->dbforge->modify_column('program_peserta', $fields);
		$hasil = $hasil && $this->server_publik();
		$hasil = $hasil && $this->convert_ip_address($hasil);
		$hasil = $hasil && $this->tambah_kolom_log_keluarga($hasil);
		$hasil = $hasil && $this->pengaturan_grup($hasil);

		status_sukses($hasil);
		return $hasil;
	}

	protected function server_publik()
	{
		// Tampilkan menu Sekretariat di pengaturan modul
		$hasil = $this->db
			->where('id', 15)
			->set('hidden', 0)
			->set('parent', 0)
			->update('setting_modul');
		$hasil = $hasil && $this->tambah_kolom_updated_at();
		$hasil = $hasil && $this->buat_tabel_ref_sinkronisasi();
		return $hasil;
	}

	// Tambah kolom untuk memungkinkkan sinkronsisasi
	protected function tambah_kolom_updated_at()
	{
		$hasil = true;
		if ( ! $this->db->field_exists('updated_at', 'tweb_keluarga'))
		{
			$hasil = $hasil && $this->dbforge->add_column('tweb_keluarga', 'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
			$hasil = $hasil && $this->dbforge->add_column('tweb_keluarga', 'updated_by int(11) NOT NULL');
		}
		return $hasil;
	}

	protected function buat_tabel_ref_sinkronisasi()
	{
		$hasil = true;
		// Buat folder unggah sinkronisasi
		mkdir(LOKASI_SINKRONISASI_ZIP, 0775, true);
  	// Tambah rincian pindah di log_penduduk
		$tabel = 'ref_sinkronisasi';
		if ($this->db->table_exists($tabel)) return $hasil;

		$this->dbforge->add_field([
			'tabel' 				=> ['type' => 'VARCHAR', 'constraint' => 100, 'null' => false],
			'server' 				=> ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'default' => null],
			'jenis_update'	=> ['type' => 'TINYINT', 'constraint' => 4, 'null' => true, 'default' => null],
			'tabel_hapus' 	=> ['type' => 'VARCHAR', 'constraint' => 100, 'null' => true, 'default' => null],
		]);
		$this->dbforge->add_key('tabel', true);
		$hasil = $hasil && $this->dbforge->create_table($tabel, true);
		$hasil = $hasil && $this->db->insert_batch(
			$tabel,
			[
				['tabel'=>'tweb_penduduk', 'server' => '6', 'jenis_update' => 1, 'tabel_hapus' => 'log_hapus_penduduk'],
				['tabel'=>'tweb_keluarga', 'server' => '6', 'jenis_update' => 1, 'tabel_hapus' => 'log_keluarga'],
			]
		);
		return $hasil;
	}

	/**
	 * Convert ip address.
	 */
	protected function convert_ip_address($hasil)
	{
		$data = $this->db
			->not_like('ipAddress', 'ip_address')
			->get('sys_traffic')
			->result();

		$batch = [];

		foreach ($data as $sys_traffic)
		{
			$remove_character = str_replace('{}', '', $sys_traffic->ipAddress);

			$batch[] = [
				'ipAddress' => json_encode(['ip_address' => [$remove_character]]),
				'Tanggal'   => $sys_traffic->Tanggal,
			];
		}

		$hasil = $hasil && $this->db->update_batch('sys_traffic', $batch, 'Tanggal');

		return $hasil >= 0;
	}

	protected function tambah_kolom_log_keluarga($hasil)
	{
		if (! $this->db->field_exists('id_pend', 'log_keluarga'))
		{
			$hasil = $hasil && $this->dbforge->add_column('log_keluarga', [
				'id_pend' => ['type' => 'INT', 'constraint' => 11, 'null' => TRUE],
				'updated_by' => ['type' => 'INT', 'constraint' => 11, 'null' => FALSE]
			]);
			$hasil = $hasil && $this->isi_ulang_log_keluarga($hasil);
		}
		if (! $this->db->field_exists('id_log_penduduk', 'log_keluarga'))
		{
			$hasil = $hasil && $this->dbforge->add_column('log_keluarga', [
				'id_log_penduduk' => ['type' => 'INT', 'constraint' => 10, 'null' => TRUE],
			]);
			$hasil = $hasil && $this->dbforge->add_column('log_keluarga', [
				'CONSTRAINT `log_penduduk_fk` FOREIGN KEY (`id_log_penduduk`) REFERENCES `log_penduduk` (`id`) ON DELETE CASCADE ON UPDATE CASCADE'
			]);
		}
		// Pindahkan log_penduduk lama ke log_keluarga
		// Perhatikan pemindahan ini tidak akan dilakukan jika semua log id_peristiwa = 7
		// terhapus pada Migrasi_fitur_premium_2102.php
		$log_keluar = $this->db
			->select('l.id as id, l.id_pend, k.id as id_kk, p2.sex as kk_sex')
			->where('l.kode_peristiwa', 7)
			->from('log_penduduk l')
			->join('tweb_penduduk p1', 'p1.id = l.id_pend')
			->join('tweb_keluarga k', 'k.no_kk = p1.no_kk_sebelumnya', 'left')
			->join('tweb_penduduk p2', 'p2.id = k.nik_kepala', 'left')
			->get()
			->result_array();
		if (count($log_keluar) == 0) return $hasil;
		$data = [];
		foreach ($log_keluar as $log)
		{
			if ( ! $log['id_kk']) continue; // Abaikan kasus keluar dari keluarga
			$data[] = [
				'id_peristiwa' => 12,
				'tgl_peristiwa' => $log['tgl_peristiwa'],
				'updated_by' => $log['updated_by'] ?: $this->session->user,
				'id_kk' => $log['id_kk'],
				'kk_sex' => $log['kk_sex'],
				'id_pend' => $log['id_pend']
			];
		}
		$hasil = $hasil && $this->db->insert_batch('log_keluarga', $data);
		$hasil = $hasil && $this->db
			->where_in('id', array_column($log_keluar, 'id'))
			->delete('log_penduduk');
		return $hasil;
	}

	// Catat ulang semua keluarga di log_keluarga untuk laporan bulanan
	private function isi_ulang_log_keluarga($hasil)
	{
		// Kosongkan
		$this->db->truncate('log_keluarga');
		// Tambah keluarga yg ada sebagai keluarga baru
		$keluarga = $this->db
			->select('k.id as id_kk, p.sex as kk_sex, "1" as id_peristiwa, tgl_daftar as tgl_peristiwa, "1" as updated_by')
			->from('tweb_keluarga k')
			->join('tweb_penduduk p', 'p.id = k.nik_kepala')
			->get()->result_array();
		$hasil = $hasil && $this->db->insert_batch('log_keluarga', $keluarga);

		// Tambah mutasi keluarga
		$mutasi = $this->db
			->select('k.id as id_kk, p.sex as kk_sex, lp.tgl_lapor as tgl_peristiwa')
			->select('(case when lp.kode_peristiwa in (2, 3, 4) then lp.kode_peristiwa end) as id_peristiwa')
			->select('"1" as updated_by')
			->from('tweb_keluarga k')
			->join('tweb_penduduk p', 'p.id = k.nik_kepala')
			->join('log_penduduk lp', 'lp.id_pend = p.id and lp.kode_peristiwa = p.status_dasar')
			->where('p.status_dasar <>', 1)
			->get()->result_array();
		if ( ! empty($mutasi))
		{
			$hasil = $hasil && $this->db->insert_batch('log_keluarga', $mutasi);
		}
		return $hasil;
	}

	protected function pengaturan_grup($hasil)
	{
		$this->cache->hapus_cache_untuk_semua('_cache_modul');
		$hasil = $hasil && $this->modul_tambahan($hasil);
		$hasil = $hasil && $this->ubah_grup($hasil);
		$hasil = $hasil && $this->tambah_grup_akses($hasil);
		$hasil = $hasil && $this->urut_modul($hasil);
		$hasil = $hasil && $this->bersihkan_modul($hasil);
		$hasil = $hasil && $this->akses_grup_bawaan($hasil);
		return $hasil;
	}

	private function ubah_grup($hasil)
	{
		$fields = [
			'id' => ['type' => 'INT', 'constraint' => 5, 'auto_increment' => TRUE],
		];
		$hasil = $hasil && $this->dbforge->modify_column('user_grup', $fields);
		if (! $this->db->field_exists('created_by', 'user_grup'))
			$hasil = $hasil && $this->dbforge->add_column('user_grup', [
				'jenis' => ['type' => 'TINYINT', 'constraint' => 2, 'null' => FALSE, 'default' => 1],
				'created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
				'created_by' => ['type' => 'INT', 'constraint' => 11, 'null' => TRUE],
				'updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
				'updated_by' => ['type' => 'INT', 'constraint' => 11, 'null' => FALSE]
			]);
		// Grup tambahan
		$hasil = $hasil && $this->db->where('id >', 4)->update('user_grup', ['jenis' => 2]);

		return $hasil;
	}

	private function tambah_grup_akses($hasil)
	{
		if ($this->db->table_exists('grup_akses')) return $hasil;

		$this->dbforge->add_field([
			'id' => ['type' => 'INT', 'constraint' => 11, 'auto_increment' => true],
			'id_grup'	=> ['type' => 'INT', 'null' => false],
			'id_modul'	=> ['type' => 'INT', 'null' => false],
			'akses'	=> ['type' => 'TINYINT', 'null' => true],
		]);

		$this->dbforge->add_key('id', true);
		$this->dbforge->add_key('id_grup');
		$this->dbforge->add_key('id_modul');
		$hasil = $hasil && $this->dbforge->create_table('grup_akses', true);
		$hasil = $hasil && $this->dbforge->add_column('grup_akses', [
			'CONSTRAINT fk_id_grup FOREIGN KEY(id_grup) REFERENCES user_grup(id) ON DELETE CASCADE ON UPDATE CASCADE',
			'CONSTRAINT fk_id_modul FOREIGN KEY(id_modul) REFERENCES setting_modul(id) ON DELETE CASCADE ON UPDATE CASCADE',
		]);

		return $hasil;
	}

	private function urut_modul($hasil)
	{
		$urut = [
			['id' => 206, 'urut' => 5], // Siaga Covid-19
			['id' => 1, 'urut' => 10], // Home
			['id' => 200, 'urut' => 20], // Info Desa
			['id' => 2, 'urut' => 30], // Kependudukan
			['id' => 3, 'urut' => 40], // Statistik
			['id' => 4, 'urut' => 50], // Layanan Surat
			['id' => 15, 'urut' => 60], // Sekretariat
			['id' => 301, 'urut' => 70], // Buku Administrasi Desa
			['id' => 201, 'urut' => 80], // Keuangan
			['id' => 5, 'urut' => 90], // Analisis
			['id' => 6, 'urut' => 100], // Bantuan
			['id' => 7, 'urut' => 110], // Pertanahan
			['id' => 220, 'urut' => 120], // Pembangunan
			['id' => 9, 'urut' => 130], // Pemetaan
			['id' => 10, 'urut' => 140], // SMS
			['id' => 11, 'urut' => 150], // Pengaturan
			['id' => 13, 'urut' => 160], // Admin Web
			['id' => 14, 'urut' => 170], // Layanan Mandiri
		];
		$fields = [
			'urut' => ['type' => 'INT', 'constraint' => 4],
		];
		$hasil = $hasil && $this->dbforge->modify_column('setting_modul', $fields);

		foreach ($urut as $modul)
		{
			$hasil = $hasil && $this->db
				->where('id', $modul['id'])
				->update('setting_modul', ['urut' => $modul['urut']]);
		}
		return $hasil;
	}

	private function akses_grup_bawaan($hasil)
	{
		// Operator, Redaksi, Kontributor, Satgas Covid-19
		$hasil = $hasil && $this->db->where('id_grup in (2, 3, 4, 5)')->delete('grup_akses');
		$query = "
			INSERT INTO grup_akses (`id_grup`, `id_modul`, `akses`) VALUES
			-- Operator --
			(2,1,3),
			(2,2,0),
			(2,3,0),
			(2,4,0),
			(2,5,0),
			(2,6,3),
			(2,7,0),
			(2,8,3),
			(2,9,0),
			(2,10,0),
			(2,11,0),
			(2,13,0),
			(2,14,0),
			(2,15,0),
			(2,17,3),
			(2,18,3),
			(2,20,3),
			(2,21,3),
			(2,22,3),
			(2,23,3),
			(2,24,3),
			(2,25,3),
			(2,26,3),
			(2,27,3),
			(2,28,3),
			(2,29,3),
			(2,30,3),
			(2,31,3),
			(2,32,3),
			(2,33,3),
			(2,39,3),
			(2,40,3),
			(2,41,3),
			(2,42,3),
			(2,47,3),
			(2,48,3),
			(2,49,3),
			(2,50,3),
			(2,51,3),
			(2,52,3),
			(2,53,3),
			(2,54,3),
			(2,55,3),
			(2,56,3),
			(2,57,3),
			(2,58,3),
			(2,61,3),
			(2,62,3),
			(2,63,3),
			(2,64,3),
			(2,67,3),
			(2,68,3),
			(2,69,3),
			(2,70,3),
			(2,71,3),
			(2,72,3),
			(2,73,3),
			(2,95,3),
			(2,97,3),
			(2,98,3),
			(2,101,3),
			(2,200,0),
			(2,201,0),
			(2,202,3),
			(2,203,3),
			(2,205,3),
			(2,206,0),
			(2,207,7),
			(2,208,7),
			(2,209,3),
			(2,210,3),
			(2,211,3),
			(2,212,3),
			(2,213,3),
			(2,220,0),
			(2,221,3),
			(2,301,0),
			(2,302,3),
			(2,303,3),
			(2,304,3),
			(2,305,3),
			(2,306,3),
			(2,312,3),
			(2,313,3),
			(2,314,3),
			(2,310,3),
			(2,311,3),
			(2,315,3),
			(2,316,3),
			(2,317,3),
			(2,318,3),
			-- Redaksi --
			(3,13,0),
			(3,47,7),
			(3,48,7),
			(3,49,7),
			(3,50,7),
			(3,51,7),
			(3,53,7),
			(3,54,7),
			(3,64,7),
			(3,205,7),
			(3,211,7),
			-- Kontributor --
			(4,13,0),
			(4,47,3),
			(4,50,3),
			(4,51,3),
			(4,54,3),
			-- Satgas Covid-19 --
			(5,3,0),
			(5,27,3),
			(5,206,0),
			(5,207,7),
			(5,208,7)
		";
		$hasil = $hasil && $this->db->query($query);
		return $hasil;
	}

	// Kosongkan url modul yg mempunyai sub modul
	private function bersihkan_modul($hasil)
	{
		// Semua modul utama
		$this->db
			->select('id, modul')
			->from('setting_modul')
			->where('parent', 0);
		$modul = $this->db->get_compiled_select();

		// Modul utama yg mempunyai sub
		$ada_sub = $this->db
			->distinct()
			->select('m.id, m.modul')
			->from('('.$modul.') as m')
			->join('setting_modul sub', 'sub.parent = m.id and sub.hidden <> 2' )
			->where('sub.id is not null')
			->order_by('m.id')
			->get()->result_array();
		$ada_sub = array_column($ada_sub, 'id');
		// Kosongkan url modul utama yg mempunyai sub
		$hasil = $hasil && $this->db
			->set('url', '')
			->where_in('id', $ada_sub)
			->update('setting_modul');

		return $hasil;
	}

	// Beri nilai default setting_modul utk memudahkan menambah modul
	private function modul_tambahan($hasil)
	{
	  $this->db->like('url', 'man_user')->update('setting_modul', ['url' => 'man_user/clear']);
		$fields = [
			'ikon' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => ''],
			'ikon_kecil' => ['type' => 'VARCHAR', 'constraint' => 50, 'null' => true, 'default' => ''],
		];
		$hasil = $hasil && $this->dbforge->modify_column('setting_modul', $fields);
		$hasil = $hasil && $this->tambah_modul([
			'id'         => 102,
			'modul'      => 'Pengaturan Grup',
			'url'        => 'grup/clear',
			'aktif'      => 1,
			'urut'       => 0,
			'level'      => 0,
			'hidden'     => 2,
			'parent'     => 44
		]);

		return $hasil;
	}
}
