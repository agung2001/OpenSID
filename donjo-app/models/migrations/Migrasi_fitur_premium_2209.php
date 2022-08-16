<?php

/*
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
 * Hak Cipta 2016 - 2022 Perkumpulan Desa Digital Terbuka (https://opendesa.id)
 *
 * Dengan ini diberikan izin, secara gratis, kepada siapa pun yang mendapatkan salinan
 * dari perangkat lunak ini dan file dokumentasi terkait ("Aplikasi Ini"), untuk diperlakukan
 * tanpa batasan, termasuk hak untuk menggunakan, menyalin, mengubah dan/atau mendistribusikan,
 * asal tunduk pada syarat berikut:
 *
 * Pemberitahuan hak cipta di atas dan pemberitahuan izin ini harus disertakan dalam
 * setiap salinan atau bagian penting Aplikasi Ini. Barang siapa yang menghapus atau menghilangkan
 * pemberitahuan ini melanggar ketentuan lisensi Aplikasi Ini.
 *
 * PERANGKAT LUNAK INI DISEDIAKAN "SEBAGAIMANA ADANYA", TANPA JAMINAN APA PUN, BAIK TERSURAT MAUPUN
 * TERSIRAT. PENULIS ATAU PEMEGANG HAK CIPTA SAMA SEKALI TIDAK BERTANGGUNG JAWAB ATAS KLAIM, KERUSAKAN ATAU
 * KEWAJIBAN APAPUN ATAS PENGGUNAAN ATAU LAINNYA TERKAIT APLIKASI INI.
 *
 * @package   OpenSID
 * @author    Tim Pengembang OpenDesa
 * @copyright Hak Cipta 2009 - 2015 Combine Resource Institution (http://lumbungkomunitas.net/)
 * @copyright Hak Cipta 2016 - 2022 Perkumpulan Desa Digital Terbuka (https://opendesa.id)
 * @license   http://www.gnu.org/licenses/gpl.html GPL V3
 * @link      https://github.com/OpenSID/OpenSID
 *
 */

use App\Models\FormatSurat;
use App\Models\KB;
use App\Models\Pamong;
use App\Models\RefJabatan;
use Illuminate\Support\Facades\DB;

defined('BASEPATH') || exit('No direct script access allowed');

class Migrasi_fitur_premium_2209 extends MY_model
{
    public function up()
    {
        $hasil = true;

        // Jalankan migrasi sebelumnya
        $hasil = $hasil && $this->jalankan_migrasi('migrasi_fitur_premium_2208');
        $hasil = $hasil && $this->migrasi_2022080271($hasil);
        $hasil = $hasil && $this->migrasi_2022070551($hasil);
        $hasil = $hasil && $this->migrasi_2022080471($hasil);
        $hasil = $hasil && $this->migrasi_2022080571($hasil);
        $hasil = $hasil && $this->migrasi_2022080451($hasil);
        $hasil = $hasil && $this->migrasi_2022080971($hasil);
        $hasil = $hasil && $this->migrasi_2022081071($hasil);
        $hasil = $hasil && $this->migrasi_2022081171($hasil);

        return $hasil && $this->migrasi_2022081671($hasil);
    }

    protected function migrasi_2022080271($hasil)
    {
        $hasil = $hasil && $this->tambah_setting([
            'key'        => 'jenis_peta',
            'value'      => '5',
            'keterangan' => 'Jenis peta yang digunakan',
            'jenis'      => 'option-kode',
        ]);

        $id_setting = $this->db->get_where('setting_aplikasi', ['key' => 'jenis_peta'])->row()->id;

        if ($id_setting) {
            $this->db->where('id_setting', $id_setting)->delete('setting_aplikasi_options');

            $hasil = $hasil && $this->db->insert_batch(
                'setting_aplikasi_options',
                [
                    ['id_setting' => $id_setting, 'kode' => '1', 'value' => 'OpenStreetMap'],
                    ['id_setting' => $id_setting, 'kode' => '2', 'value' => 'OpenStreetMap H.O.T'],
                    ['id_setting' => $id_setting, 'kode' => '3', 'value' => 'Mapbox Streets'],
                    ['id_setting' => $id_setting, 'kode' => '4', 'value' => 'Mapbox Satellite'],
                    ['id_setting' => $id_setting, 'kode' => '5', 'value' => 'Mapbox Satellite-Street'],
                ]
            );
        }

        return $hasil;
    }

    protected function migrasi_2022070551($hasil)
    {
        $hasil && $this->tambah_setting([
            'key'        => 'verifikasi_kades',
            'value'      => '0',
            'keterangan' => 'Verifikasi Surat Oleh Kepala Desa',
            'kategori'   => 'alur_surat',
            'jenis'      => 'boolean',
        ]);

        $hasil && $this->tambah_setting([
            'key'        => 'verifikasi_sekdes',
            'value'      => '0',
            'keterangan' => 'Verifikasi Surat Oleh Sekretaris daerah',
            'kategori'   => 'alur_surat',
            'jenis'      => 'boolean',
        ]);

        $hasil && $this->tambah_setting([
            'key'        => 'verifikasi_operator',
            'value'      => '1',
            'keterangan' => 'Verifikasi Surat Oleh Operator (Layanan Mandiri)',
            'kategori'   => 'alur_surat',
            'jenis'      => 'boolean',
        ]);

        if (! $this->db->field_exists('verifikasi_sekdes', 'log_surat')) {
            $fields = [
                'verifikasi_sekdes' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'null'       => true,
                    'after'      => 'status',
                ],
            ];
            $hasil = $hasil && $this->dbforge->add_column('log_surat', $fields);
        }

        if (! $this->db->field_exists('verifikasi_kades', 'log_surat')) {
            $fields = [
                'verifikasi_kades' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'null'       => true,
                    'after'      => 'status',
                ],
            ];
            $hasil = $hasil && $this->dbforge->add_column('log_surat', $fields);
        }

        if (! $this->db->field_exists('verifikasi_operator', 'log_surat')) {
            $fields = [
                'verifikasi_operator' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'null'       => true,
                    'after'      => 'status',
                ],
            ];
            $hasil = $hasil && $this->dbforge->add_column('log_surat', $fields);
        }

        if (! $this->db->field_exists('tte', 'log_surat')) {
            $fields = [
                'tte' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'null'       => true,
                    'after'      => 'status',
                ],
            ];
            $hasil = $hasil && $this->dbforge->add_column('log_surat', $fields);
        }

        if (! $this->db->field_exists('log_verifikasi', 'log_surat')) {
            $fields = [
                'log_verifikasi' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => true,
                    'after'      => 'status',
                ],
            ];
            $hasil = $hasil && $this->dbforge->add_column('log_surat', $fields);
        }

        return $hasil && $this->ubah_modul(32, ['url' => 'keluar/clear/masuk']);
    }

    protected function migrasi_2022080471($hasil)
    {
        if (! $this->db->table_exists('ref_jabatan')) {
            // Tambah tabel ref_jabatan
            $ref_jabatan = [
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 10,
                    'null'           => false,
                    'auto_increment' => true,
                ],
                'nama' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 100,
                    'null'       => false,
                ],
                'tupoksi' => [
                    'type'    => 'LONGTEXT',
                    'null'    => true,
                    'default' => null,
                ],
                'jenis' => [
                    'type'       => 'TINYINT',
                    'constraint' => 1,
                    'default'    => 0,
                    'null'       => false,
                ],
            ];
            $hasil = $hasil && $this->dbforge
                ->add_key('id', true)
                ->add_field($ref_jabatan)
                ->create_table('ref_jabatan', true);

            if (! $this->db->field_exists('jabatan_id', 'tweb_desa_pamong')) {
                // Tambah field jabatan_id
                $tweb_desa_pamong['jabatan_id'] = [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'null'       => false,
                ];
                $hasil = $hasil && $this->dbforge->add_column('tweb_desa_pamong', $tweb_desa_pamong);
            }

            $jabatan = DB::table('tweb_desa_pamong')->select(['pamong_id', 'jabatan', 'pamong_ttd', 'pamong_ub'])->orderBy('pamong_ttd', 'desc')->orderBy('pamong_ub', 'desc')->get();
            if ($jabatan) {
                $simpan = collect($jabatan)->unique('jabatan')->map(static function ($item, $key) {
                    Pamong::where('jabatan', $item->jabatan)->update(['jabatan_id' => $key + 1]);

                    return [
                        'id'    => $key + 1,
                        'nama'  => $item->jabatan,
                        'jenis' => ($item->pamong_ttd == 1 || $item->pamong_ub == 1) ? 1 : 0,
                    ];
                })
                    ->values()
                    ->toArray();

                if ($simpan) {
                    RefJabatan::insert($simpan);
                }
            }

            $hasil = $hasil && $this->timestamps('ref_jabatan', true);

            // Hapus field pamong_id
            if ($this->db->field_exists('pamong_id', 'config')) {
                $hasil = $hasil && $this->dbforge->drop_column('config', 'pamong_id');
            }

            // Hapus field nip_kepala_desa
            if ($this->db->field_exists('nip_kepala_desa', 'config')) {
                $hasil = $hasil && $this->dbforge->drop_column('config', 'nip_kepala_desa');
            }

            $hasil = $hasil && $this->timestamps('config', true);

            // Hapus field jabatan
            if ($this->db->field_exists('jabatan', 'tweb_desa_pamong')) {
                $hasil = $hasil && $this->dbforge->drop_column('tweb_desa_pamong', 'jabatan');
            }
        }

        return $hasil;
    }

    protected function migrasi_2022080571($hasil)
    {
        $hasil = $hasil && $this->ubah_modul(32, ['urut' => 4]);

        return $hasil && $this->ubah_modul(98, ['url' => 'permohonan_surat_admin', 'urut' => 3, 'parent' => 4]);
    }

    protected function migrasi_2022080451($hasil)
    {
        return $hasil && $this->tambah_setting([
            'key'        => 'notifikasi_koneksi',
            'value'      => 1,
            'keterangan' => 'Ingatkan jika aplikasi tidak terhubung dengan internet.',
            'jenis'      => 'boolean',
        ]);
    }

    protected function migrasi_2022080971($hasil)
    {
        return $hasil && $this->tambah_setting([
            'key'        => 'tampil_luas_peta',
            'value'      => 0,
            'keterangan' => 'Tampilkan Luas Wilayah Pada Peta',
            'jenis'      => 'boolean',
        ]);
    }

    protected function migrasi_2022081071($hasil)
    {
        // Jalankan hanya jika terdeksi cara lama (kades = a.n)
        if (Pamong::where('jabatan_id', 1)->where('pamong_ttd', 1)->exists()) {
            // Sesuaikan Penanda tangan kepala desa
            $hasil = $hasil && Pamong::where('pamong_ttd', 1)->update(['pamong_ttd' => 0, 'pamong_ub' => 0]);
        }

        // Jalankan hanya jika terdeksi cara lama (sekdes = u.b)
        if (Pamong::where('jabatan_id', 2)->where('pamong_ub', 1)->exists()) {
            // Sesuaikan Penanda tangan sekdes (a.n)
            $hasil = $hasil && Pamong::where('pamong_ub', 1)->update(['pamong_ttd' => 1, 'pamong_ub' => 0]);
        }

        // Bagian ini di lewati, default tidak ada terpilih
        // Untuk penanda tangan u.b perlu disesuaikan ulang agar menyesuaikan

        return $hasil;
    }

    protected function migrasi_2022081171($hasil)
    {
        return $hasil && KB::updateOrCreate([
            'id' => 100,
        ], [
            'id'   => 100,
            'nama' => 'Tidak Menggunakan',
            'sex'  => 3,
        ]);
    }

    protected function migrasi_2022081671($hasil)
    {
        if (! $this->db->field_exists('form_isian', 'tweb_surat_format')) {
            $fields['form_isian'] = [
                'type'  => 'LONGTEXT',
                'null'  => true,
                'after' => 'template_desa',
            ];

            $hasil = $hasil && $this->dbforge->add_column('tweb_surat_format', $fields);

            // Sesuaikan data awal surat tinymce
            FormatSurat::jenis(FormatSurat::TINYMCE)->update(['form_isian' => '{"individu":{"sex":"","status_dasar":""}}']);
        }
        if (! $this->db->field_exists('berat_badan', 'bulanan_anak')) {
            $fields = [
                'berat_badan' => [
                    'type'  => 'FLOAT',
                    'null'  => true,
                    'after' => 'pengukuran_berat_badan',
                ],
            ];
            $hasil = $hasil && $this->dbforge->add_column('bulanan_anak', $fields);
        }

        if (! $this->db->field_exists('tinggi_badan', 'bulanan_anak')) {
            $fields = [
                'tinggi_badan' => [
                    'type'       => 'INT',
                    'constraint' => 4,
                    'null'       => true,
                    'after'      => 'pengukuran_tinggi_badan',
                ],
            ];
            $hasil = $hasil && $this->dbforge->add_column('bulanan_anak', $fields);
          }
        return $hasil;
    }
}
