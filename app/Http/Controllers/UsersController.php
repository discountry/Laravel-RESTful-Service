<?php

namespace App\Http\Controllers;

use App\User;
use App\Http\Controllers\Controller;
use Dingo\Api\Routing\Helpers;

class UsersController extends Controller
{
	use Helpers;

    public function __construct()
    {
        $this->scopes('read_user_data',array('index','show'));
    }

    public function index()
    {
        return User::all();
    }

    public function show($id)
    {
        return User::findOrFail($id);
    }
}
