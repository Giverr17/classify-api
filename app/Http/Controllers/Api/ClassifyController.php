<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ClassifyController extends Controller
{
    public function classify(Request $request)
    {
        //Check if name parameter exists
        $name = $request->query('name');

        if (is_null($name) || $name === '') {
            return response()->json([
                "status" => "error",
                "message" => "Name parameter is required"
            ], 400);
        }

        // Check if name is a string
        if (!is_string($name)) {
            return response()->json([
                "status" => "error",
                "message" => "Name must be a string"
            ], 422);
        }

        // Call the Genderize API
        try {
            $response = Http::timeout(5)
            ->withoutVerifying()
            ->get('https://api.genderize.io', [
                'name' => $name
            ]); 
        } catch (\Exception $e) {
            return response()->json([
                "status" => "error",
                "message" => "Failed to reach external API",
                "debug" => $e->getMessage()

            ], 502);
        }

        //Handle API failure
        if (!$response->successful()) {
            return response()->json([
                "status" => "error",
                "message" => "Failed to fetch data from external API"
            ], 502);
        }

        //Extract data
        $data = $response->json();

        //Handle edge case — null gender or zero count
        if (is_null($data['gender']) || $data['count'] == 0) {
            return response()->json([
                "status" => "error",
                "message" => "No prediction available for the provided name"
            ], 422);
        }

        //Process and transform the data
        $sample_size = $data['count'];
        $probability = $data['probability'];
        $is_confident = ($probability >= 0.7 && $sample_size >= 100);
        $processed_at = now()->utc()->toISOString();

        //Return success response
        return response()->json([
            "status" => "success",
            "data" => [
                "name"         => strtolower($name),
                "gender"       => $data['gender'],
                "probability"  => $probability,
                "sample_size"  => $sample_size,
                "is_confident" => $is_confident,
                "processed_at" => $processed_at
            ]
        ], 200);
    }
}