<?php

namespace Pterodactyl\Http\Controllers\Admin\Extensions\resourcemanager;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;
use Illuminate\Support\Facades\File;
use Pterodactyl\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\View\Factory as ViewFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Pterodactyl\Contracts\Repository\SettingsRepositoryInterface;
use Illuminate\Http\RedirectResponse;
use Pterodactyl\BlueprintFramework\Libraries\ExtensionLibrary\Admin\BlueprintAdminLibrary as BlueprintExtensionLibrary;
use Pterodactyl\Http\Requests\Admin\AdminFormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Exception;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class resourcemanagerExtensionController extends Controller
{
    public function __construct(
        private ViewFactory $view,
        private BlueprintExtensionLibrary $blueprint,
        private ConfigRepository $config,
        private SettingsRepositoryInterface $settings,
    ){}

    //UPLOAD HANDLER ---------------------------------------------------------------------------
    public function index(Request $request)
    {
        if (!$request->user() || !$request->user()->root_admin) {
            throw new AccessDeniedHttpException();
        }
    
        return $this->view->make(
            'admin.extensions.{identifier}.index', [
              'root' => "/admin/extensions/{identifier}",
              'blueprint' => $this->blueprint,
            ]
          );
    }

    public function uploadImage(Request $request)
    {
    if (!$request->user() || !$request->user()->root_admin) {
        throw new AccessDeniedHttpException();
    }

    $request->validate([
        'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:20480', // Validate image file
    ]);

    $file = $request->file('image');
    $filename = time() . '_' . $file->getClientOriginalName();
    $path = public_path('extensions/resourcemanager/uploads');

    if (!file_exists($path)) {
        mkdir($path, 0755, true); // Create the uploads directory if it doesn't exist
    }

    $file->move($path, $filename);

    return response()->json(['success' => true, 'message' => 'Image uploaded successfully.', 'url' => "/extensions/resourcemanager/uploads/$filename"]);
    }

    public function listImages(Request $request)
    {
    if (!$request->user() || !$request->user()->root_admin) {
        throw new AccessDeniedHttpException();
    }

    $path = public_path('extensions/resourcemanager/uploads');
    $files = [];

    if (file_exists($path)) {
        $files = array_map(function ($file) {
            return [
                'name' => basename($file),
                'url' => asset("extensions/resourcemanager/uploads/" . basename($file)),
            ];
        }, glob($path . '/*'));
    }

    return response()->json(['success' => true, 'files' => $files]);
    }

    public function deleteImage(Request $request)
    {
        if (!$request->user() || !$request->user()->root_admin) {
            throw new AccessDeniedHttpException();
        }
    
        $request->validate([
            'filename' => 'required|string',
        ]);
    
        $uploadsDir = public_path('extensions/resourcemanager/uploads');
    
        $filename = basename($request->input('filename'));
    
        $filePath = $uploadsDir . DIRECTORY_SEPARATOR . $filename;
    
        if (file_exists($filePath) && strpos(realpath($filePath), realpath($uploadsDir)) === 0) {
            unlink($filePath);
            return response()->json(['success' => true, 'message' => 'Image deleted successfully.']);
        }
    
        return response()->json(['success' => false, 'message' => 'File not found or invalid.'], 404);
    }

}