<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileUploadController extends Controller
{
    public function store(Request $request)
    {
        /**
         * Campos para subida de foto de perfil
         *  Usermodel
         */
        $request->validate([
            'archivos' => 'required|array',
            'archivos.*' => 'file|max:20480|mimes:jpg,jpeg,png,pdf,docx',
        ]);

        $urls = [];

        foreach ($request->file('archivos') as $archivo) {
            $nombre = uniqid().'.'.$archivo->getClientOriginalExtension();
            $path = $archivo->storeAs('uploads', $nombre, 'public');
            $urls[] = asset("storage/$path");
        }

        return $this->success(
            ['path' => $urls],
            'Archivo subido con éxito',
            201
        );
    }
}
