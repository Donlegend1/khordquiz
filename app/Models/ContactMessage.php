<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'email', 'subject', 'message', 'ip_address', 'attachment_path', 'attachment_name'])]
class ContactMessage extends Model
{
    //
}
