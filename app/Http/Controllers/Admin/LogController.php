<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Inertia\Inertia;

class LogController extends Controller
{
    public function index(Request $request)
    {
        $selectedFile = $request->query('file', 'laravel.log');
        $logPath = storage_path('logs/' . $selectedFile);
        
        $files = collect(File::files(storage_path('logs')))
            ->filter(fn($file) => $file->getExtension() === 'log')
            ->map(fn($file) => [
                'name' => $file->getFilename(),
                'size' => round($file->getSize() / 1024, 2) . ' KB',
                'last_modified' => date('Y-m-d H:i:s', $file->getMTime()),
            ])
            ->sortByDesc('last_modified')
            ->values();

        $logs = [];

        if (File::exists($logPath)) {
            $content = File::get($logPath);
            $parts = preg_split("/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/m", $content, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
            
            for ($i = 0; $i < count($parts); $i += 2) {
                if (isset($parts[$i + 1])) {
                    $date = $parts[$i];
                    $part = $parts[$i + 1];
                    $firstLineEnd = strpos($part, "\n");
                    $firstLine = $firstLineEnd !== false ? substr($part, 0, $firstLineEnd) : $part;
                    $remaining = $firstLineEnd !== false ? substr($part, $firstLineEnd + 1) : '';
                    preg_match("/ (\w+)\.(\w+): (.*)/", $firstLine, $matches);
                    
                    $logs[] = [
                        'id' => $i,
                        'date' => $date,
                        'env' => $matches[1] ?? 'unknown',
                        'level' => $matches[2] ?? 'INFO',
                        'message' => $matches[3] ?? trim($firstLine),
                        'stack' => trim($remaining),
                        'full' => "[{$date}]" . $part
                    ];
                }
            }
            $logs = array_reverse($logs);
        }

        return Inertia::render('Admin/Logs', [
            'logs' => $logs,
            'files' => $files,
            'currentFile' => $selectedFile
        ]);
    }

    public function clear($file)
    {
        $path = storage_path('logs/' . $file);
        if (File::exists($path)) {
            File::put($path, '');
            return redirect()->back()->with('success', "Le fichier $file a été vidé avec succès.");
        }
        return redirect()->back()->with('error', 'Fichier introuvable.');
    }

    public function delete($file)
    {
        $path = storage_path('logs/' . $file);
        if (File::exists($path)) {
            File::delete($path);
            return redirect()->route('admin.logs.viewer')->with('success', "Le fichier $file a été supprimé.");
        }
        return redirect()->back()->with('error', 'Fichier introuvable.');
    }

    public function download($file)
    {
        $path = storage_path('logs/' . $file);
        if (File::exists($path)) {
            return Response::download($path);
        }
        return redirect()->back()->with('error', 'Fichier introuvable.');
    }
}
