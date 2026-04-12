<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ChatMessage;
use OpenAI;

class ChatController extends Controller {
    private $crisisKeywords = [
        // English keywords
        "suicide", "kill myself", "want to die", "self harm", "cut myself",
        "end my life", "don’t want to live", "i'm done", "give up",
        "no reason to live", "worthless", "hopeless",
        "i wish i was gone", "disappear", "better off dead",

        // Tagalog keywords
        "pagod na ako", "ayoko na", "gusto ko nang mawala",
        "wala nang kwenta", "hindi ko na kaya",
        "magpakamatay", "saktan ang sarili"
    ];

    private $safeResponse = 
        "I'm really sorry you're feeling this way. You don’t have to go through this alone.
        It would really help to reach out to someone you trust or a counselor. If you feel unsafe, please contact emergency services or a crisis hotline right now.
        I'm here with you—do you want to share what’s been going on?";

        private $mentalHealthKeywords = [
        // emotions
        "i feel sad", "i feel anxious", "i feel tired", "i feel empty",
        "i feel lost", "i feel overwhelmed",

        // stress
        "too much pressure", "academic pressure", "burned out",
        "drained", "can't focus", "no motivation",

        // social/emotional
        "no one understands me", "i feel alone", "no friends",
        "i need someone to talk to",

        // school context
        "exam stress", "failing", "low grades", "school stress", "capstone", "studies", "defense", "prototype",
        "internship", "graduation", "thesis",

        // indirect
        "not okay", "something feels off", "can i talk", "need someone"
        ];
    
    private $systemPrompt = 
    "You are LeanOn Bot, a supportive AI mental health companion designed specifically for students.

    Your goal is to provide a safe, empathetic, and non-judgmental space where students can express their thoughts, emotions, and struggles.

    CORE PRINCIPLES:
    - Always prioritize empathy, understanding, and emotional validation.
    - Never judge, shame, or dismiss the user’s feelings.
    - Listen actively and reflect emotions before giving suggestions.
    - Keep responses warm, human-like, and conversational (not robotic).

    TONE & STYLE:
    - Speak like a supportive friend, not a therapist or authority figure.
    - Use simple, clear, and comforting language (avoid jargon).
    - Be calm, gentle, and reassuring.
    - Keep responses concise but meaningful.

    WHAT YOU SHOULD DO:
    1. Acknowledge feelings:
        - Validate emotions 
    2. Encourage expression:
        - Ask open-ended, gentle questions.
    3. Offer coping strategies:
        - Suggest simple, safe techniques.
    4. Normalize experiences:
        - Help students feel they are not alone.
    5. Promote self-care and reflection.
    6. Adapt responses based on context.

    WHAT YOU MUST NOT DO:
    - Do NOT provide medical diagnoses.
    - Do NOT prescribe medication or clinical treatment.
    - Do NOT act as a licensed therapist.
    - Do NOT give harmful, extreme, or absolute advice.

    CRISIS HANDLING (VERY IMPORTANT):
    If the user expresses:
    - suicidal thoughts
    - self-harm intentions
    - feeling unsafe

    You MUST:
    1. Respond with empathy and urgency.
    2. Encourage seeking real human help immediately.
    3. Suggest contacting:
    - trusted person (friend, family, counselor)
    - local crisis hotline or emergency services
    4. Never leave the user unsupported or dismiss their feelings.

    BOUNDARIES:
    - Be honest about limitations: “I’m here to support, but I’m not a professional.”
    - Encourage professional help when needed.

    PRIVACY & TRUST:
    - Reinforce that this is a safe space.
    - Do not ask for unnecessary personal or sensitive data.

    CONVERSATION FLOW:
    - Start warm and welcoming.
    - Use follow-up questions to deepen understanding.
    - Avoid overwhelming the user with too many suggestions at once.
    - If the user is answering a question you previously asked, continue the conversation naturally and build on their answer. Do not restart or switch topics.

    SPECIAL FOR STUDENTS:
    - Understand common student struggles:
    (academic pressure, deadlines, burnout, social anxiety, family expectations)
    - Tailor advice specifically to student life.

    SCOPE LIMITATION:
    - You are ONLY for mental health and emotional support for students.
    - If a user asks unrelated questions, gently redirect the conversation back to their well-being.

    GOAL:
    Help the user feel heard, supported, calmer, and slightly better than before the conversation.";

    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'conversation_id' => 'required|exists:conversations,id'
        ]);

        $userMessage = $request->input('message');
        $userId = $request->user('sanctum') ? $request->user('sanctum')->id : null;
        $conversationId = $request->input('conversation_id');

        // Update conversation last message
        $conversation = \App\Models\Conversation::find($conversationId);
        if ($conversation) {
            $conversation->last_message = substr($userMessage, 0, 50) . (strlen($userMessage) > 50 ? '...' : '');
            if ($conversation->title === 'New Chat') {
                $conversation->title = $conversation->last_message;
            }
            $conversation->save();
        }

        // Check for crisis keywords
        $isCrisis = $this->checkForCrisis($userMessage);

        if ($isCrisis) {
            // Save to DB
            ChatMessage::create([
                'user_id' => $userId,
                'conversation_id' => $conversationId,
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
                'conversation_id' => $conversationId,
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
            // Fetch conversation history (last 10 messages)
            $history = ChatMessage::where('conversation_id', $conversationId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->reverse();

            $contents = [];
            
            // Add history to contents
            foreach ($history as $msg) {
                $contents[] = [
                    'role' => 'user',
                    'parts' => [['text' => $msg->message]]
                ];
                $contents[] = [
                    'role' => 'model',
                    'parts' => [['text' => $msg->reply]]
                ];
            }

            // Add current message
            $contents[] = [
                'role' => 'user',
                'parts' => [['text' => $userMessage]]
            ];

            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders([
                    'Content-Type' => 'application/json',
                ])->post('https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey, [
                    'system_instruction' => [
                        'parts' => [
                            ['text' => $this->systemPrompt]
                        ]
                    ],
                    'contents' => $contents
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
                'conversation_id' => $conversationId,
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
        $conversationId = $request->query('conversation_id');

        // Require auth to get history, or just get by session/IP (for simplicity, we assume auth if user_id is tracked)
        $userId = $request->user('sanctum') ? $request->user('sanctum')->id : null;

        if ($userId && $conversationId) {
            $history = ChatMessage::where('user_id', $userId)
                        ->where('conversation_id', $conversationId)
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
        preg_match('/feel|feeling|talk|alone|sad|tired|lost|help|stress|anxious|study|studies|capstone|exam|assignment|school|defense|project/i', $messageLower)
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
