<?php

namespace App\Models;

use CodeIgniter\Model;

class DebtModel extends Model
{
    protected $table = 'debts';
    protected $primaryKey = 'id';
    protected $allowedFields = ['namaAplikasi', 'jumlahPinjaman', 'jumlahTenor'];

    public function getWithCicilan()
    {
        $db = \Config\Database::connect();
        $sql = "
        SELECT d.*, 
               c.id AS cicilan_id, 
               c.tanggal, 
               c.jumlah AS cicilan_jumlah
        FROM debts d
        LEFT JOIN cicilan c ON c.debt_id = d.id
        ORDER BY d.id DESC, c.tanggal ASC
    ";
        $rows = $db->query($sql)->getResultArray();

        $result = [];
        foreach ($rows as $row) {
            $id = $row['id'];

            // init
            if (!isset($result[$id])) {
                $result[$id] = [
                    'id' => $row['id'],

                    // ALIAS AGAR SESUAI VIEW
                    'name' => $row['namaAplikasi'],
                    'total' => $row['jumlahPinjaman'],

                    'paid' => 0,
                    'tenor' => $row['jumlahTenor'],
                    'cicilan' => []
                ];
            }

            // cicilan
            if ($row['cicilan_id']) {
                $result[$id]['cicilan'][] = [
                    'id' => $row['cicilan_id'],
                    'tanggal' => $row['tanggal'],
                    'jumlah' => $row['cicilan_jumlah']
                ];

                // tambahkan ke paid total
                $result[$id]['paid'] += $row['cicilan_jumlah'];
            }
        }

        return array_values($result);
    }

    /**
     * Ambil daftar nama aplikasi unik untuk dropdown
     * @return array
     */
    public function getDistinctNamaAplikasi()
    {
        $builder = $this->builder(); // builder otomatis untuk table 'debts'
        $builder->select('namaAplikasi')->distinct();
        $builder->orderBy('namaAplikasi', 'ASC');

        $result = $builder->get()->getResultArray();

        return array_map(fn($row) => $row['namaAplikasi'], $result);
    }
}
