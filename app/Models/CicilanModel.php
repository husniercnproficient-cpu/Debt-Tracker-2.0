<?php

namespace App\Models;

use CodeIgniter\Model;

class CicilanModel extends Model
{
    protected $table = 'cicilan';
    protected $primaryKey = 'id';
    protected $allowedFields = ['debt_id', 'tanggal', 'jumlah'];
}
