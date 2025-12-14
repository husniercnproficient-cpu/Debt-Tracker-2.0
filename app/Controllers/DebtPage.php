<?php

namespace App\Controllers;

class DebtPage extends BaseController
{
    public function index()
    {
        return view('debt/debts_list');
    }
}
