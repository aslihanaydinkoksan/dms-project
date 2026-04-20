<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Interfaces\AssistantServiceInterface;

class AssistantController extends Controller
{
    protected AssistantServiceInterface $assistant;

    // Dependency Injection (SOLID'e uygun şekilde Interface'i içeri alıyoruz)
    public function __construct(AssistantServiceInterface $assistant)
    {
        $this->assistant = $assistant;
    }

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:500'
        ]);

        $response = $this->assistant->ask($request->message, $request->user());

        return response()->json($response);
    }
}
