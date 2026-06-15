<?php
namespace App\Http\Middleware;
use Closure;
class ValidateImageContent
{
    public function handle($request, Closure $next)
    {
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file->path());
            finfo_close($finfo);
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            if (!in_array($mime, $allowed)) {
                return response()->json(['message' => 'File bukan gambar yang valid.'], 422);
            }
        }
        return $next($request);
    }
}