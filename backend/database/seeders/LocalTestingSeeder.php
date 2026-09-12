<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Conversation;
use App\Models\ChatMessage;
use App\Models\MoodEntry;
use App\Models\EmotionLog;
use App\Models\CrisisAlert;
use App\Models\AdminNotification;

class LocalTestingSeeder extends Seeder
{
    /**
     * Run the database seeds for rich local testing.
     */
    public function run(): void
    {
        $this->command->info('Seeding local test data for LeanOn-Bot...');

        // 1. Ensure Baseline Seed Users
        $guidance = User::updateOrCreate(
            ['email' => 'AdminUser@gordoncollege.edu.ph'],
            [
                'first_name' => 'Guidance',
                'last_name' => 'Officer',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('AdminPassword@123'),
                'role' => 'guidance',
                'department' => null,
                'program' => null,
                'year_level' => null,
                'phone_number' => '09123456780',
                'terms_accepted_at' => Carbon::now(),
            ]
        );

        $studentIra = User::updateOrCreate(
            ['email' => '202311473@gordoncollege.edu.ph'],
            [
                'first_name' => 'Ira Jacob',
                'last_name' => 'Javier',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('IraJacobUser@123'),
                'age' => 22,
                'gender' => 'Male',
                'role' => 'student',
                'department' => 'CCS',
                'program' => 'Bachelor of Science in Information Technology',
                'year_level' => '3rd Year',
                'phone_number' => '09999999991',
                'terms_accepted_at' => Carbon::now(),
            ]
        );

        $studentAllysa = User::updateOrCreate(
            ['email' => '202310636@gordoncollege.edu.ph'],
            [
                'first_name' => 'Allysa',
                'last_name' => 'Lingad',
                'email_verified_at' => Carbon::now(),
                'password' => Hash::make('AllysaUser@123'),
                'age' => 22,
                'gender' => 'Female',
                'role' => 'student',
                'department' => 'CCS',
                'program' => 'Bachelor of Science in Information Technology',
                'year_level' => '3rd Year',
                'phone_number' => '09999999992',
                'terms_accepted_at' => Carbon::now(),
            ]
        );

        // 2. Additional Students Across Departments
        $testStudentsData = [
            [
                'email' => '202310101@gordoncollege.edu.ph',
                'first_name' => 'Mark Anthony',
                'last_name' => 'Santos',
                'age' => 21,
                'gender' => 'Male',
                'role' => 'student',
                'department' => 'CCS',
                'program' => 'Bachelor of Science in Computer Science',
                'year_level' => '2nd Year',
                'phone_number' => '09999999993',
            ],
            [
                'email' => '202310202@gordoncollege.edu.ph',
                'first_name' => 'Bea Nicole',
                'last_name' => 'Ramos',
                'age' => 20,
                'gender' => 'Female',
                'role' => 'student',
                'department' => 'CBA',
                'program' => 'Bachelor of Science in Business Administration',
                'year_level' => '2nd Year',
                'phone_number' => '09999999994',
            ],
            [
                'email' => '202310303@gordoncollege.edu.ph',
                'first_name' => 'Christian David',
                'last_name' => 'Cruz',
                'age' => 19,
                'gender' => 'Male',
                'role' => 'student',
                'department' => 'CAHS',
                'program' => 'Bachelor of Science in Nursing',
                'year_level' => '1st Year',
                'phone_number' => '09999999995',
            ],
            [
                'email' => '202310404@gordoncollege.edu.ph',
                'first_name' => 'Danielle Joy',
                'last_name' => 'Flores',
                'age' => 23,
                'gender' => 'Female',
                'role' => 'student',
                'department' => 'CEAS',
                'program' => 'Bachelor of Secondary Education',
                'year_level' => '4th Year',
                'phone_number' => '09999999996',
            ],
        ];

        $students = [$studentIra, $studentAllysa];
        foreach ($testStudentsData as $sData) {
            $student = User::updateOrCreate(
                ['email' => $sData['email']],
                array_merge($sData, [
                    'email_verified_at' => Carbon::now(),
                    'password' => Hash::make('Student@123'),
                    'terms_accepted_at' => Carbon::now(),
                ])
            );
            $students[] = $student;
        }

        $this->command->info('Users seeded: ' . count($students) . ' students + 1 guidance officer.');

        // 3. Populate Conversations & Chat Messages
        $conversationsData = [
            [
                'user' => $studentIra,
                'title' => 'Managing Capstone Deadlines & Anxiety',
                'messages' => [
                    [
                        'msg' => 'Hi LeanOn Bot, I have been feeling extremely anxious about our upcoming capstone defense. I feel like I am letting my group down.',
                        'reply' => "Hello Ira. It's completely valid to feel the weight of capstone deadlines—they can be genuinely intimidating. Remember that your worth isn't determined by a single deadline, and doing your best is what matters. Have you tried breaking down your tasks into small, manageable milestones today?",
                        'emotion' => 'anxious',
                    ],
                    [
                        'msg' => 'I tried, but every time I sit down to code, my chest feels tight and I cannot focus.',
                        'reply' => 'That physical sensation is a clear sign your nervous system is in overdrive. Let\'s pause together right now. Take a slow, deep breath in for 4 seconds, hold for 4, and exhale gently for 6. You don\'t have to finish everything today. What is just one tiny task you can do next?',
                        'emotion' => 'stressed',
                    ],
                    [
                        'msg' => 'Thank you, that breathing exercise helped steady me a bit. I will just fix the database seeder first.',
                        'reply' => 'That is a fantastic, concrete first step! Celebrate small wins. I am always right here whenever you need a breather.',
                        'emotion' => 'calm',
                    ]
                ]
            ],
            [
                'user' => $studentAllysa,
                'title' => 'Feeling Burned Out and Exhausted',
                'messages' => [
                    [
                        'msg' => 'I feel so drained lately. Even getting out of bed for my 7 AM classes feels impossible.',
                        'reply' => 'Hi Allysa, thank you for sharing that with me. Academic burnout is very real, especially when you\'ve been pushing yourself hard without real rest. Have you been able to get proper sleep and nutrition recently?',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg' => 'Barely. I have back-to-back quizzes and assignments and I haven\'t taken a day off in weeks.',
                        'reply' => 'Your body and mind are asking for kindness. Please remember that taking rest is not slacking off—it\'s necessary maintenance. Even 15 minutes away from screens to listen to music or walk can help reset your mental energy.',
                        'emotion' => 'stressed',
                    ]
                ]
            ],
            [
                'user' => $students[2], // Mark Anthony Santos
                'title' => 'Struggling with Algorithms and Impostor Syndrome',
                'messages' => [
                    [
                        'msg' => 'Everyone in my programming class seems to get Data Structures so quickly, and I am falling behind.',
                        'reply' => 'Impostor syndrome is so common in tech courses, Mark. People often showcase their confidence while hiding their own struggles. Everyone learns at a different pace, and understanding concepts deeply takes time.',
                        'emotion' => 'anxious',
                    ],
                    [
                        'msg' => 'Yeah, I guess you\'re right. I will ask my professor for clarification during office hours.',
                        'reply' => 'That is an excellent proactive decision! Seeking help is a strength, not a weakness. Good luck, you\'ve got this!',
                        'emotion' => 'hopeful',
                    ]
                ]
            ],
            [
                'user' => $students[3], // Bea Nicole Ramos
                'title' => 'Financial Stress and Part-time Work',
                'messages' => [
                    [
                        'msg' => 'Balancing working part-time and studying for finals is overwhelming me.',
                        'reply' => 'Balancing a job and university studies is a tremendous accomplishment in itself, Bea. It shows great resilience, but it\'s also very demanding. Let\'s look at ways you can protect your wellbeing during exam week.',
                        'emotion' => 'overwhelmed',
                    ]
                ]
            ]
        ];

        // Clean previous test conversational and analytical data for an idempotent run
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ChatMessage::truncate();
        Conversation::truncate();
        EmotionLog::truncate();
        CrisisAlert::truncate();
        MoodEntry::truncate();
        AdminNotification::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        foreach ($conversationsData as $cIdx => $cData) {
            $user = $cData['user'];
            $lastMsg = end($cData['messages'])['msg'];

            $conversation = Conversation::create([
                'user_id' => $user->id,
                'email' => $user->email,
                'title' => $cData['title'],
                'last_message' => $lastMsg,
                'is_saved' => ($cIdx % 2 === 0),
                'is_archived' => false,
                'created_at' => Carbon::now()->subDays(rand(1, 10)),
                'updated_at' => Carbon::now()->subHours(rand(1, 24)),
            ]);

            foreach ($cData['messages'] as $mIdx => $m) {
                $msgTime = Carbon::now()->subDays(rand(1, 7))->addMinutes($mIdx * 5);

                ChatMessage::create([
                    'user_id' => $user->id,
                    'conversation_id' => $conversation->id,
                    'message' => $m['msg'],
                    'reply' => $m['reply'],
                    'is_crisis' => false,
                    'is_fallback' => false,
                    'created_at' => $msgTime,
                    'updated_at' => $msgTime,
                ]);
            }

            // Create one emotion log per conversation
            $lastEmotion = end($cData['messages'])['emotion'] ?? 'neutral';
            EmotionLog::create([
                'user_id' => $user->id,
                'conversation_id' => $conversation->id,
                'emotion' => $lastEmotion,
                'created_at' => Carbon::now()->subDays(rand(1, 7)),
                'updated_at' => Carbon::now()->subDays(rand(1, 7)),
            ]);
        }

        // 4. Create Flagged Messages & Crisis Alerts for Testing
        $crisisStudent = $students[4]; // Christian David Cruz
        $crisisConv = Conversation::create([
            'user_id' => $crisisStudent->id,
            'email' => $crisisStudent->email,
            'title' => 'Urgent: Difficult Thoughts and Overwhelm',
            'last_message' => 'I feel completely hopeless and like I cannot keep doing this anymore. Everything is too much.',
            'is_saved' => true,
            'is_archived' => false,
            'created_at' => Carbon::now()->subHours(12),
            'updated_at' => Carbon::now()->subHours(2),
        ]);

        $crisisMsg = ChatMessage::create([
            'user_id' => $crisisStudent->id,
            'conversation_id' => $crisisConv->id,
            'message' => 'I feel completely hopeless and like I cannot keep doing this anymore. Everything is too much.',
            'reply' => 'I hear how much pain and overwhelm you are feeling right now, and I want you to know that your life has immense value. You are not alone in this. Please connect with our Gordon College Guidance Center or reach out to someone you trust immediately.',
            'is_crisis' => true,
            'is_fallback' => false,
            'created_at' => Carbon::now()->subHours(2),
            'updated_at' => Carbon::now()->subHours(2),
        ]);

        EmotionLog::create([
            'user_id' => $crisisStudent->id,
            'conversation_id' => $crisisConv->id,
            'emotion' => 'hopeless',
            'created_at' => Carbon::now()->subHours(2),
            'updated_at' => Carbon::now()->subHours(2),
        ]);

        // Crisis Alert 1: Severe / Scheduled
        $alert1 = CrisisAlert::create([
            'user_id' => $crisisStudent->id,
            'chat_message_id' => $crisisMsg->id,
            'department' => $crisisStudent->department,
            'gender' => $crisisStudent->gender,
            'message' => $crisisMsg->message,
            'severity' => 'severe',
            'detected_keywords' => ['hopeless', 'cannot keep doing this', 'too much'],
            'flag_reason' => 'High crisis distress markers detected in conversation',
            'status' => 'reviewed',
            'is_classified' => true,
            'admin_email_sent_at' => Carbon::now()->subHour(),
            'admin_email_notified' => true,
            'appointment_date' => Carbon::tomorrow()->toDateString(),
            'appointment_time' => '10:00 AM',
            'appointment_status' => 'scheduled',
            'created_at' => Carbon::now()->subHours(2),
            'updated_at' => Carbon::now()->subHour(),
        ]);

        // Crisis Alert 2: Moderate / Unreviewed (New Alert to test Guidance triage)
        $alert2Student = $students[5]; // Danielle Joy Flores
        $alert2Conv = Conversation::create([
            'user_id' => $alert2Student->id,
            'email' => $alert2Student->email,
            'title' => 'Panic attack before practice teaching',
            'last_message' => 'I had a severe panic attack earlier and my heart won\'t stop racing.',
            'is_saved' => false,
            'is_archived' => false,
            'created_at' => Carbon::now()->subHours(5),
            'updated_at' => Carbon::now()->subHours(1),
        ]);

        $alert2Msg = ChatMessage::create([
            'user_id' => $alert2Student->id,
            'conversation_id' => $alert2Conv->id,
            'message' => 'I had a severe panic attack earlier and my heart won\'t stop racing.',
            'reply' => 'Panic attacks can feel terrifying, but please remember you are safe. Let\'s focus on grounding yourself: look around and name 5 things you can see, 4 you can touch, and 3 you can hear.',
            'is_crisis' => true,
            'is_fallback' => false,
            'created_at' => Carbon::now()->subHours(1),
            'updated_at' => Carbon::now()->subHours(1),
        ]);

        $alert2 = CrisisAlert::create([
            'user_id' => $alert2Student->id,
            'chat_message_id' => $alert2Msg->id,
            'department' => $alert2Student->department,
            'gender' => $alert2Student->gender,
            'message' => $alert2Msg->message,
            'severity' => 'moderate',
            'detected_keywords' => ['panic attack', 'racing'],
            'flag_reason' => 'Severe acute anxiety symptoms reported',
            'status' => 'new',
            'is_classified' => true,
            'admin_email_sent_at' => null,
            'admin_email_notified' => false,
            'appointment_date' => null,
            'appointment_time' => null,
            'appointment_status' => null,
            'created_at' => Carbon::now()->subHours(1),
            'updated_at' => Carbon::now()->subHours(1),
        ]);

        // 5. Admin Notifications
        AdminNotification::crisisFlagged($alert1);
        AdminNotification::crisisFlagged($alert2);

        // 6. Mood Entries Spread Over the Last 20 Days
        $moodOptions = ['happy', 'neutral', 'stressed', 'anxious', 'sad', 'calm', 'overwhelmed'];
        foreach ($students as $stu) {
            for ($d = 20; $d >= 0; $d--) {
                if (rand(0, 10) > 4) {
                    $selectedMood = $moodOptions[array_rand($moodOptions)];
                    $moodTime = Carbon::now()->subDays($d)->subHours(rand(1, 12));

                    MoodEntry::create([
                        'user_id' => $stu->id,
                        'mood' => $selectedMood,
                        'created_at' => $moodTime,
                        'updated_at' => $moodTime,
                    ]);
                }
            }
        }

        $this->command->info('Local test data successfully seeded!');
    }
}
