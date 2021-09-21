<?php

namespace App\Http\Controllers;

use App\Services\FormatFileService;
use Illuminate\Http\Request;

class ProcessSalesController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function processFile(Request $request)
    {
        $this->validate($request, [
            'file' => 'required|file|mimes:txt'
        ]);

        $fileContent = file_get_contents($request->file);
        $fileContent = explode(PHP_EOL, $fileContent);
        $fileContent = array_filter($fileContent);
    
        $formatFile = new FormatFileService();

        $formatedFile = [];

        foreach ($fileContent as $content) {
            $formatFile->setContent($content);
            $formatedFile[] = $formatFile->read()->format()->get();
        }
        
        return response()->json([
            'sales' => $formatedFile
        ]);
    }
}
