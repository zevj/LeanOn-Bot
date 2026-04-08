<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatMessage;
use OpenAI;

class ChatController extends Controller
{
    private $crisisKeywords = [
        "suicide", "kill myself", "want to die", "self harm", "cut myself"
    ];

    private $safeResponse = "I'm really sorry you're feeling this much pain right now. 
    You don’t have to go through this alone. 
    It might really help to reach out to someone you trust or a counselor at Gordon College. 
    If you're in immediate danger, please seek urgent help. 
    Would you be open to talking about what’s been weighing on you?";

    private $mentalHealthKeywords = [
        "stress", "anxious", "anxiety", "depress", "sad", "feel", "emotion", "overwhelm",
        "exam", "school", "grade", "pressure", "academic", "class", "study", "campus", "gordon college",
        "alone", "lonely", "help", "worry", "worried", "panic", "cry", "mental", "burnout", "friend",
        "tired", "exhausted", "sleep", "hard", "difficult", "struggle", "cope",
        "hi", "hello", "hey", "good morning", "good afternoon", "good evening", "bot",
        // indirect emotional expressions
        'talk to someone', 'need someone', 'feel off',
        'not okay', 'lonely', 'empty', 'lost',
        'i feel', 'feeling', 'can i talk',
    ];
    

    private $systemPrompt = "You are Lean On Bot, a supportive mental health assistant specifically for Gordon College students.
- You MUST limit responses ONLY to mental health topics (stress, anxiety, emotions, academic pressure).
- Politely refuse ALL unrelated topics (coding, math, general knowledge, etc.).
- Use an empathetic, calm, non-judgmental tone (Claude-style).
- RESPONSE STYLE:
- Be natural, warm, and conversational (like a real person).
- Avoid repeating the same sentence starters.
- Vary how you respond each time.
- Keep responses short (2–4 sentences).
- You may:
  • acknowledge feelings
  • validate emotions
  • offer support
  • ask a gentle question
  But do NOT follow a rigid structure every time.
- Occasionally start with:
  • Hey, I’m here for you.
  • That sounds really tough.
  • I get why you’d feel that way.
  • Thanks for sharing that.
- Do NOT provide medical advice.
CRISIS HANDLING RULES:
- ONLY provide hotlines or emergency resources if the user clearly expresses intent to harm themselves (e.g., I want to kill myself, I will end my life).
- DO NOT provide hotlines for vague words like die, kill, or unclear context.
- If intent is unclear, respond with empathy and ask a gentle follow-up question instead.
- Direct students to Guidance Office or trusted adults if they mention suicide or self-harm.
- You may reference Gordon College campus life, coursework, or general academic struggles to build rapport.";

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $userMessage = $request->input('message');
        $userId = $request->user('sanctum') ? $request->user('sanctum')->id : null;

        // Check for crisis keywords
        $isCrisis = $this->checkForCrisis($userMessage);

        if ($isCrisis) {
            // Save to DB
            ChatMessage::create([
                'user_id' => $userId,
                'message' => $userMessage,
                'reply' => $this->safeResponse,
                'is_crisis' => true,
            ]);

            return response()->json([
                'reply' => $this->safeResponse,
                'is_crisis' => true,
            ]);
        }

        // Check if message is related to mental health (Keyword Filter)
        $isMentalHealthRelated = $this->checkForMentalHealthTopic($userMessage);

        if (!$isMentalHealthRelated) {
            $refusalMessage = "I'm here to support mental health and well-being. Would you like to share how you're feeling?";
            
            // Save refusal to DB
            ChatMessage::create([
                'user_id' => $userId,
                'message' => $userMessage,
                'reply' => $refusalMessage,
                'is_crisis' => false,
            ]);

            return response()->json([
                'reply' => $refusalMessage
            ]);
        }

        // Call Google Gemini API
        $apiKey = env('GEMINI_API_KEY');
        
        if (empty($apiKey)) {
            return response()->json([
                'reply' => 'Error: Google Gemini API key is missing. Please add GEMINI_API_KEY to your .env file.',
                'error' => true
            ], 500);
        }

        try {
            // Combine system prompt + user message for Gemini
            $combinedPrompt = $this->systemPrompt . "\n\nUser: " . $userMessage;

            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $combinedPrompt]
                            ]
                        ]
                    ]
                ]);

            if ($response->successful()) {
                // Extract response exactly how Gemini format structures it
                $aiReply = $response->json('candidates.0.content.parts.0.text') ?? 'Sorry, I am unable to process that right now.';
            } else {
                $errorMessage = $response->json('error.message') ?? $response->status();
                $aiReply = 'Sorry, there was an error communicating with the AI. (' . $errorMessage . ')';
            }

            // Save to DB
            ChatMessage::create([
                'user_id' => $userId,
                'message' => $userMessage,
                'reply' => $aiReply,
                'is_crisis' => false,
            ]);

            return response()->json([
                'reply' => $aiReply
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'reply' => 'Sorry, I am having trouble connecting to my brain right now.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function history(Request $request)
    {
        // Require auth to get history, or just get by session/IP (for simplicity, we assume auth if user_id is tracked)
        $userId = $request->user('sanctum') ? $request->user('sanctum')->id : null;

        if ($userId) {
            $history = ChatMessage::where('user_id', $userId)
                        ->orderBy('created_at', 'asc')
                        ->get();
        } else {
            // If completely public without session, maybe just return empty or mock
            // since we can't reliably fetch a guest's history across requests without a session tracking mechanism
            $history = collect([]);
        }

        return response()->json($history);
    }

    private function checkForCrisis($message)
    {
        $messageLower = strtolower($message);
        foreach ($this->crisisKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks if the message contains words related to mental health or academics.
     */
    private function checkForMentalHealthTopic($message)
{
    $messageLower = strtolower($message);
    $isMentalHealth = false;

    // 1. Allow short emotional messages
    if (
        str_word_count($messageLower) <= 8 ||
        preg_match('/feel|feeling|talk|alone|sad|tired|lost|help|stress|anxious/i', $messageLower)
    ) {
        $isMentalHealth = true;
    }

    // 2. Fallback to keyword list (your existing array)
    foreach ($this->mentalHealthKeywords as $keyword) {
        if (str_contains($messageLower, $keyword)) {
            $isMentalHealth = true;
            break;
        }
    }

    return $isMentalHealth;
}
}
