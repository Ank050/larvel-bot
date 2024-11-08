<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use LucianoTonet\GroqPHP\Groq;

class ChatbotController extends Controller
{
    protected $groq;

    public function __construct()
    {
        // Pass the API key directly as a string
        $this->groq = new Groq(env('GROQ_API_KEY'));
    }

    public function getResponse(Request $request)
    {
        $request->validate(['message' => 'required|string']);
        $userMessage = $request->input('message');

        try {
            // Create a chat completion request
            $response = $this->groq->chat()->completions()->create([
                'model' => 'llama-3.1-8b-instant',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $userMessage,
                    ]
                ],
            ]);

            $botResponse = $response['choices'][0]['message']['content'] ?? 'No response received.';
            // echo "Bot: " . $botResponse . "\n"; // Print to terminal
            return response()->json(['response' => trim($botResponse)]); // Ensure the response is trimmed
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error processing request: ' . $e->getMessage()], 500);
        }
    }

    public function getResponseWithTools(Request $request)
    {
        $request->validate(['message' => 'required|string']);
        $userMessage = $request->input('message');

        // Define the tools available for the assistant
        $tools = [
            [
                "type" => "function",
                "function" => [
                    "name" => "calendar_tool",
                    "description" => "Gets the current time in a specific format.",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "format" => [
                                "type" => "string",
                                "description" => "The format of the time to return."
                            ],
                        ],
                        "required" => ["format"],
                    ],
                ]
            ],
            [
                "type" => "function",
                "function" => [
                    "name" => "weather_tool",
                    "description" => "Gets the current weather conditions of a location.",
                    "parameters" => [
                        "type" => "object",
                        "properties" => [
                            "location" => [
                                "type" => "string",
                                "description" => "Location to get weather information."
                            ],
                        ],
                        "required" => ["location"],
                    ],
                ]
            ],
        ];

        $messages = [
            [
                'role' => 'user',
                'content' => $userMessage,
            ]
        ];

        try {
            // Send request with tool calling capabilities
            $response = $this->groq->chat()->completions()->create([
                'model' => 'mixtral-8x7b-32768',
                'messages' => $messages,
                'tool_choice' => 'auto',
                'tools' => $tools
            ]);

            foreach ($response['choices'][0]['message']['tool_calls'] as $tool_call) {
                $function_args = json_decode($tool_call['function']['arguments'], true);
                $function_name = $tool_call['function']['name'];

                // Call the tool's function
                if (method_exists($this, $function_name)) {
                    $function_response = $this->$function_name($function_args);
                } else {
                    $function_response = "Function $function_name not implemented.";
                }

                // Append tool response to messages for final response
                $messages[] = [
                    'role' => 'tool',
                    'name' => $function_name,
                    'content' => $function_response,
                ];
            }

            // Send final message for response after tool calls
            $finalResponse = $this->groq->chat()->completions()->create([
                'model' => 'mixtral-8x7b-32768',
                'messages' => $messages
            ]);

            $botResponse = $finalResponse['choices'][0]['message']['content'] ?? 'No response received.';
            echo "Bot: " . $botResponse . "\n"; // Print to terminal
            return response()->json(['response' => trim($botResponse)]); // Ensure the response is trimmed

        } catch (\Exception $e) {
            return response()->json(['error' => 'Error processing request with tools: ' . $e->getMessage()], 500);
        }
    }

    // Define tool functions
    private function calendar_tool($args)
    {
        return date($args['format'] ?? 'd-m-Y');
    }

    private function weather_tool($args)
    {
        // Simulate weather data
        return "Weather in {$args['location']}: Sunny, 25°C";
    }
}
