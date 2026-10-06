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
use App\Models\SessionLog;

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
                'first_name'        => 'Guidance',
                'last_name'         => 'Officer',
                'email_verified_at' => Carbon::now(),
                'password'          => Hash::make('AdminPassword@123'),
                'role'              => 'guidance',
                'department'        => null,
                'program'           => null,
                'year_level'        => null,
                'phone_number'      => '09123456780',
                'terms_accepted_at' => Carbon::now(),
            ]
        );

        $studentIra = User::updateOrCreate(
            ['email' => '202311473@gordoncollege.edu.ph'],
            [
                'first_name'        => 'Ira Jacob',
                'last_name'         => 'Javier',
                'email_verified_at' => Carbon::now(),
                'password'          => Hash::make('IraJacobUser@123'),
                'age'               => 22,
                'gender'            => 'Male',
                'role'              => 'student',
                'department'        => 'CCS',
                'program'           => 'Bachelor of Science in Information Technology',
                'year_level'        => '3rd Year',
                'phone_number'      => '09999999991',
                'terms_accepted_at' => Carbon::now(),
            ]
        );

        $studentAllysa = User::updateOrCreate(
            ['email' => '202310636@gordoncollege.edu.ph'],
            [
                'first_name'        => 'Allysa',
                'last_name'         => 'Lingad',
                'email_verified_at' => Carbon::now(),
                'password'          => Hash::make('AllysaUser@123'),
                'age'               => 22,
                'gender'            => 'Female',
                'role'              => 'student',
                'department'        => 'CCS',
                'program'           => 'Bachelor of Science in Information Technology',
                'year_level'        => '3rd Year',
                'phone_number'      => '09999999992',
                'terms_accepted_at' => Carbon::now(),
            ]
        );

        // 2. Additional Students Across Departments (original 4)
        $testStudentsData = [
            [
                'email'      => '202310101@gordoncollege.edu.ph',
                'first_name' => 'Mark Anthony',
                'last_name'  => 'Santos',
                'age'        => 21,
                'gender'     => 'Male',
                'role'       => 'student',
                'department' => 'CCS',
                'program'    => 'Bachelor of Science in Computer Science',
                'year_level' => '2nd Year',
                'phone_number' => '09999999993',
            ],
            [
                'email'      => '202310202@gordoncollege.edu.ph',
                'first_name' => 'Bea Nicole',
                'last_name'  => 'Ramos',
                'age'        => 20,
                'gender'     => 'Female',
                'role'       => 'student',
                'department' => 'CBA',
                'program'    => 'Bachelor of Science in Business Administration',
                'year_level' => '2nd Year',
                'phone_number' => '09999999994',
            ],
            [
                'email'      => '202310303@gordoncollege.edu.ph',
                'first_name' => 'Christian David',
                'last_name'  => 'Cruz',
                'age'        => 19,
                'gender'     => 'Male',
                'role'       => 'student',
                'department' => 'CAHS',
                'program'    => 'Bachelor of Science in Nursing',
                'year_level' => '1st Year',
                'phone_number' => '09999999995',
            ],
            [
                'email'      => '202310404@gordoncollege.edu.ph',
                'first_name' => 'Danielle Joy',
                'last_name'  => 'Flores',
                'age'        => 23,
                'gender'     => 'Female',
                'role'       => 'student',
                'department' => 'CEAS',
                'program'    => 'Bachelor of Secondary Education',
                'year_level' => '4th Year',
                'phone_number' => '09999999996',
            ],
            // ---- 50 NEW DUMMY STUDENTS ----
            [
                'email' => '202310501@gordoncollege.edu.ph', 'first_name' => 'Rafael', 'last_name' => 'Dela Cruz',
                'age' => 20, 'gender' => 'Male', 'role' => 'student', 'department' => 'CCS',
                'program' => 'Bachelor of Science in Information Technology', 'year_level' => '2nd Year', 'phone_number' => '09180000001',
            ],
            [
                'email' => '202310502@gordoncollege.edu.ph', 'first_name' => 'Sophia Mae', 'last_name' => 'Garcia',
                'age' => 21, 'gender' => 'Female', 'role' => 'student', 'department' => 'CAHS',
                'program' => 'Bachelor of Science in Nursing', 'year_level' => '2nd Year', 'phone_number' => '09180000002',
            ],
            [
                'email' => '202310503@gordoncollege.edu.ph', 'first_name' => 'Juan Carlo', 'last_name' => 'Reyes',
                'age' => 22, 'gender' => 'Male', 'role' => 'student', 'department' => 'CBA',
                'program' => 'Bachelor of Science in Accountancy', 'year_level' => '3rd Year', 'phone_number' => '09180000003',
            ],
            [
                'email' => '202310504@gordoncollege.edu.ph', 'first_name' => 'Maria Luisa', 'last_name' => 'Villanueva',
                'age' => 19, 'gender' => 'Female', 'role' => 'student', 'department' => 'CEAS',
                'program' => 'Bachelor of Elementary Education', 'year_level' => '1st Year', 'phone_number' => '09180000004',
            ],
            [
                'email' => '202310505@gordoncollege.edu.ph', 'first_name' => 'Angelo', 'last_name' => 'Mendoza',
                'age' => 23, 'gender' => 'Male', 'role' => 'student', 'department' => 'CCS',
                'program' => 'Bachelor of Science in Computer Science', 'year_level' => '4th Year', 'phone_number' => '09180000005',
            ],
            [
                'email' => '202310506@gordoncollege.edu.ph', 'first_name' => 'Kristine Joy', 'last_name' => 'Pascual',
                'age' => 20, 'gender' => 'Female', 'role' => 'student', 'department' => 'CBA',
                'program' => 'Bachelor of Science in Business Administration', 'year_level' => '2nd Year', 'phone_number' => '09180000006',
            ],
            [
                'email' => '202310507@gordoncollege.edu.ph', 'first_name' => 'Emmanuel', 'last_name' => 'Torres',
                'age' => 21, 'gender' => 'Male', 'role' => 'student', 'department' => 'CAHS',
                'program' => 'Bachelor of Science in Midwifery', 'year_level' => '2nd Year', 'phone_number' => '09180000007',
            ],
            [
                'email' => '202310508@gordoncollege.edu.ph', 'first_name' => 'Jasmine', 'last_name' => 'Aquino',
                'age' => 18, 'gender' => 'Female', 'role' => 'student', 'department' => 'CEAS',
                'program' => 'Bachelor of Secondary Education', 'year_level' => '1st Year', 'phone_number' => '09180000008',
            ],
            [
                'email' => '202310509@gordoncollege.edu.ph', 'first_name' => 'Patrick James', 'last_name' => 'Lim',
                'age' => 22, 'gender' => 'Male', 'role' => 'student', 'department' => 'CCS',
                'program' => 'Bachelor of Science in Information Technology', 'year_level' => '3rd Year', 'phone_number' => '09180000009',
            ],
            [
                'email' => '202310510@gordoncollege.edu.ph', 'first_name' => 'Alyssa Nicole', 'last_name' => 'Bautista',
                'age' => 20, 'gender' => 'Female', 'role' => 'student', 'department' => 'CBA',
                'program' => 'Bachelor of Science in Marketing Management', 'year_level' => '2nd Year', 'phone_number' => '09180000010',
            ],
            [
                'email' => '202310511@gordoncollege.edu.ph', 'first_name' => 'Renz Michael', 'last_name' => 'Ocampo',
                'age' => 21, 'gender' => 'Male', 'role' => 'student', 'department' => 'CCS',
                'program' => 'Bachelor of Science in Computer Science', 'year_level' => '3rd Year', 'phone_number' => '09180000011',
            ],
            [
                'email' => '202310512@gordoncollege.edu.ph', 'first_name' => 'Lovely Grace', 'last_name' => 'Navarro',
                'age' => 19, 'gender' => 'Female', 'role' => 'student', 'department' => 'CAHS',
                'program' => 'Bachelor of Science in Nursing', 'year_level' => '1st Year', 'phone_number' => '09180000012',
            ],
            [
                'email' => '202310513@gordoncollege.edu.ph', 'first_name' => 'Joshua', 'last_name' => 'Fernandez',
                'age' => 22, 'gender' => 'Male', 'role' => 'student', 'department' => 'CEAS',
                'program' => 'Bachelor of Physical Education', 'year_level' => '3rd Year', 'phone_number' => '09180000013',
            ],
            [
                'email' => '202310514@gordoncollege.edu.ph', 'first_name' => 'Andrea Mae', 'last_name' => 'Soriano',
                'age' => 20, 'gender' => 'Female', 'role' => 'student', 'department' => 'CBA',
                'program' => 'Bachelor of Science in Accountancy', 'year_level' => '2nd Year', 'phone_number' => '09180000014',
            ],
            [
                'email' => '202310515@gordoncollege.edu.ph', 'first_name' => 'Nathaniel', 'last_name' => 'Castro',
                'age' => 23, 'gender' => 'Male', 'role' => 'student', 'department' => 'CCS',
                'program' => 'Bachelor of Science in Information Technology', 'year_level' => '4th Year', 'phone_number' => '09180000015',
            ],
            [
                'email' => '202310516@gordoncollege.edu.ph', 'first_name' => 'Trisha Marie', 'last_name' => 'Guevarra',
                'age' => 21, 'gender' => 'Female', 'role' => 'student', 'department' => 'CAHS',
                'program' => 'Bachelor of Science in Nursing', 'year_level' => '2nd Year', 'phone_number' => '09180000016',
            ],
            [
                'email' => '202310517@gordoncollege.edu.ph', 'first_name' => 'Carl Angelo', 'last_name' => 'Perez',
                'age' => 20, 'gender' => 'Male', 'role' => 'student', 'department' => 'CEAS',
                'program' => 'Bachelor of Secondary Education', 'year_level' => '2nd Year', 'phone_number' => '09180000017',
            ],
            [
                'email' => '202310518@gordoncollege.edu.ph', 'first_name' => 'Nicole Anne', 'last_name' => 'Dizon',
                'age' => 22, 'gender' => 'Female', 'role' => 'student', 'department' => 'CBA',
                'program' => 'Bachelor of Science in Business Administration', 'year_level' => '3rd Year', 'phone_number' => '09180000018',
            ],
            [
                'email' => '202310519@gordoncollege.edu.ph', 'first_name' => 'Aldrich', 'last_name' => 'Manalo',
                'age' => 19, 'gender' => 'Male', 'role' => 'student', 'department' => 'CCS',
                'program' => 'Bachelor of Science in Computer Science', 'year_level' => '1st Year', 'phone_number' => '09180000019',
            ],
            [
                'email' => '202310520@gordoncollege.edu.ph', 'first_name' => 'Hannah Faye', 'last_name' => 'Aguilar',
                'age' => 21, 'gender' => 'Female', 'role' => 'student', 'department' => 'CAHS',
                'program' => 'Bachelor of Science in Midwifery', 'year_level' => '3rd Year', 'phone_number' => '09180000020',
            ],
            [
                'email' => '202310521@gordoncollege.edu.ph', 'first_name' => 'Elijah', 'last_name' => 'Reyes',
                'age' => 20, 'gender' => 'Male', 'role' => 'student', 'department' => 'CEAS',
                'program' => 'Bachelor of Elementary Education', 'year_level' => '2nd Year', 'phone_number' => '09180000021',
            ],
            [
                'email' => '202310522@gordoncollege.edu.ph', 'first_name' => 'Camille Rose', 'last_name' => 'Domingo',
                'age' => 22, 'gender' => 'Female', 'role' => 'student', 'department' => 'CBA',
                'program' => 'Bachelor of Science in Marketing Management', 'year_level' => '3rd Year', 'phone_number' => '09180000022',
            ],
            [
                'email' => '202310523@gordoncollege.edu.ph', 'first_name' => 'Lance Gabriel', 'last_name' => 'Miranda',
                'age' => 23, 'gender' => 'Male', 'role' => 'student', 'department' => 'CCS',
                'program' => 'Bachelor of Science in Information Technology', 'year_level' => '4th Year', 'phone_number' => '09180000023',
            ],
            [
                'email' => '202310524@gordoncollege.edu.ph', 'first_name' => 'Pauline Joy', 'last_name' => 'Tabora',
                'age' => 20, 'gender' => 'Female', 'role' => 'student', 'department' => 'CAHS',
                'program' => 'Bachelor of Science in Nursing', 'year_level' => '2nd Year', 'phone_number' => '09180000024',
            ],
            [
                'email' => '202310525@gordoncollege.edu.ph', 'first_name' => 'Ian Gabriel', 'last_name' => 'Corpuz',
                'age' => 21, 'gender' => 'Male', 'role' => 'student', 'department' => 'CEAS',
                'program' => 'Bachelor of Secondary Education', 'year_level' => '3rd Year', 'phone_number' => '09180000025',
            ],
            [
                'email' => '202310526@gordoncollege.edu.ph', 'first_name' => 'Abigail', 'last_name' => 'Alcantara',
                'age' => 19, 'gender' => 'Female', 'role' => 'student', 'department' => 'CBA',
                'program' => 'Bachelor of Science in Accountancy', 'year_level' => '1st Year', 'phone_number' => '09180000026',
            ],
            [
                'email' => '202310527@gordoncollege.edu.ph', 'first_name' => 'Miguel', 'last_name' => 'Villafuerte',
                'age' => 22, 'gender' => 'Male', 'role' => 'student', 'department' => 'CCS',
                'program' => 'Bachelor of Science in Computer Science', 'year_level' => '3rd Year', 'phone_number' => '09180000027',
            ],
            [
                'email' => '202310528@gordoncollege.edu.ph', 'first_name' => 'Samantha', 'last_name' => 'Lozano',
                'age' => 20, 'gender' => 'Female', 'role' => 'student', 'department' => 'CAHS',
                'program' => 'Bachelor of Science in Nursing', 'year_level' => '2nd Year', 'phone_number' => '09180000028',
            ],
            [
                'email' => '202310529@gordoncollege.edu.ph', 'first_name' => 'Jeremiah', 'last_name' => 'Bonifacio',
                'age' => 23, 'gender' => 'Male', 'role' => 'student', 'department' => 'CEAS',
                'program' => 'Bachelor of Physical Education', 'year_level' => '4th Year', 'phone_number' => '09180000029',
            ],
            [
                'email' => '202310530@gordoncollege.edu.ph', 'first_name' => 'Patricia Anne', 'last_name' => 'Bernardo',
                'age' => 21, 'gender' => 'Female', 'role' => 'student', 'department' => 'CBA',
                'program' => 'Bachelor of Science in Business Administration', 'year_level' => '3rd Year', 'phone_number' => '09180000030',
            ],
            [
                'email' => '202310531@gordoncollege.edu.ph', 'first_name' => 'Nico Rafael', 'last_name' => 'Abad',
                'age' => 20, 'gender' => 'Male', 'role' => 'student', 'department' => 'CCS',
                'program' => 'Bachelor of Science in Information Technology', 'year_level' => '2nd Year', 'phone_number' => '09180000031',
            ],
            [
                'email' => '202310532@gordoncollege.edu.ph', 'first_name' => 'Gian Carlo', 'last_name' => 'Dela Rosa',
                'age' => 22, 'gender' => 'Male', 'role' => 'student', 'department' => 'CCS',
                'program' => 'Bachelor of Science in Computer Science', 'year_level' => '3rd Year', 'phone_number' => '09180000032',
            ],
            [
                'email' => '202310533@gordoncollege.edu.ph', 'first_name' => 'Roxanne', 'last_name' => 'Estrada',
                'age' => 21, 'gender' => 'Female', 'role' => 'student', 'department' => 'CAHS',
                'program' => 'Bachelor of Science in Nursing', 'year_level' => '3rd Year', 'phone_number' => '09180000033',
            ],
            [
                'email' => '202310534@gordoncollege.edu.ph', 'first_name' => 'Adrian Kyle', 'last_name' => 'Medina',
                'age' => 19, 'gender' => 'Male', 'role' => 'student', 'department' => 'CBA',
                'program' => 'Bachelor of Science in Accountancy', 'year_level' => '1st Year', 'phone_number' => '09180000034',
            ],
            [
                'email' => '202310535@gordoncollege.edu.ph', 'first_name' => 'Bianca', 'last_name' => 'Tolentino',
                'age' => 20, 'gender' => 'Female', 'role' => 'student', 'department' => 'CEAS',
                'program' => 'Bachelor of Secondary Education', 'year_level' => '2nd Year', 'phone_number' => '09180000035',
            ],
            [
                'email' => '202310536@gordoncollege.edu.ph', 'first_name' => 'Dominic', 'last_name' => 'Vergara',
                'age' => 23, 'gender' => 'Male', 'role' => 'student', 'department' => 'CCS',
                'program' => 'Bachelor of Science in Information Technology', 'year_level' => '4th Year', 'phone_number' => '09180000036',
            ],
            [
                'email' => '202310537@gordoncollege.edu.ph', 'first_name' => 'Felicia Grace', 'last_name' => 'Sta. Maria',
                'age' => 22, 'gender' => 'Female', 'role' => 'student', 'department' => 'CBA',
                'program' => 'Bachelor of Science in Marketing Management', 'year_level' => '3rd Year', 'phone_number' => '09180000037',
            ],
            [
                'email' => '202310538@gordoncollege.edu.ph', 'first_name' => 'Kent', 'last_name' => 'Espiritu',
                'age' => 21, 'gender' => 'Male', 'role' => 'student', 'department' => 'CAHS',
                'program' => 'Bachelor of Science in Nursing', 'year_level' => '3rd Year', 'phone_number' => '09180000038',
            ],
            [
                'email' => '202310539@gordoncollege.edu.ph', 'first_name' => 'Janna Leigh', 'last_name' => 'Dela Torre',
                'age' => 20, 'gender' => 'Female', 'role' => 'student', 'department' => 'CEAS',
                'program' => 'Bachelor of Elementary Education', 'year_level' => '2nd Year', 'phone_number' => '09180000039',
            ],
            [
                'email' => '202310540@gordoncollege.edu.ph', 'first_name' => 'Arlo James', 'last_name' => 'Ibarra',
                'age' => 22, 'gender' => 'Male', 'role' => 'student', 'department' => 'CCS',
                'program' => 'Bachelor of Science in Computer Science', 'year_level' => '3rd Year', 'phone_number' => '09180000040',
            ],
            [
                'email' => '202310541@gordoncollege.edu.ph', 'first_name' => 'Ysabel', 'last_name' => 'Sison',
                'age' => 19, 'gender' => 'Female', 'role' => 'student', 'department' => 'CBA',
                'program' => 'Bachelor of Science in Accountancy', 'year_level' => '1st Year', 'phone_number' => '09180000041',
            ],
            [
                'email' => '202310542@gordoncollege.edu.ph', 'first_name' => 'Brent Francis', 'last_name' => 'Ramirez',
                'age' => 21, 'gender' => 'Male', 'role' => 'student', 'department' => 'CAHS',
                'program' => 'Bachelor of Science in Nursing', 'year_level' => '3rd Year', 'phone_number' => '09180000042',
            ],
            [
                'email' => '202310543@gordoncollege.edu.ph', 'first_name' => 'Thea Marie', 'last_name' => 'Villanueva',
                'age' => 20, 'gender' => 'Female', 'role' => 'student', 'department' => 'CEAS',
                'program' => 'Bachelor of Secondary Education', 'year_level' => '2nd Year', 'phone_number' => '09180000043',
            ],
            [
                'email' => '202310544@gordoncollege.edu.ph', 'first_name' => 'Rome Andrei', 'last_name' => 'Navarro',
                'age' => 23, 'gender' => 'Male', 'role' => 'student', 'department' => 'CCS',
                'program' => 'Bachelor of Science in Information Technology', 'year_level' => '4th Year', 'phone_number' => '09180000044',
            ],
            [
                'email' => '202310545@gordoncollege.edu.ph', 'first_name' => 'Mia Clarice', 'last_name' => 'Hernandez',
                'age' => 22, 'gender' => 'Female', 'role' => 'student', 'department' => 'CBA',
                'program' => 'Bachelor of Science in Business Administration', 'year_level' => '4th Year', 'phone_number' => '09180000045',
            ],
            [
                'email' => '202310546@gordoncollege.edu.ph', 'first_name' => 'Joshua Andrei', 'last_name' => 'Peralta',
                'age' => 20, 'gender' => 'Male', 'role' => 'student', 'department' => 'CCS',
                'program' => 'Bachelor of Science in Computer Science', 'year_level' => '2nd Year', 'phone_number' => '09180000046',
            ],
            [
                'email' => '202310547@gordoncollege.edu.ph', 'first_name' => 'Lenora', 'last_name' => 'Ocampo',
                'age' => 21, 'gender' => 'Female', 'role' => 'student', 'department' => 'CAHS',
                'program' => 'Bachelor of Science in Nursing', 'year_level' => '3rd Year', 'phone_number' => '09180000047',
            ],
            [
                'email' => '202310548@gordoncollege.edu.ph', 'first_name' => 'Kurt Ian', 'last_name' => 'Magno',
                'age' => 22, 'gender' => 'Male', 'role' => 'student', 'department' => 'CEAS',
                'program' => 'Bachelor of Physical Education', 'year_level' => '3rd Year', 'phone_number' => '09180000048',
            ],
            [
                'email' => '202310549@gordoncollege.edu.ph', 'first_name' => 'Ella Rose', 'last_name' => 'Catalan',
                'age' => 19, 'gender' => 'Female', 'role' => 'student', 'department' => 'CBA',
                'program' => 'Bachelor of Science in Marketing Management', 'year_level' => '1st Year', 'phone_number' => '09180000049',
            ],
            [
                'email' => '202310550@gordoncollege.edu.ph', 'first_name' => 'Zach Daniel', 'last_name' => 'Puzon',
                'age' => 21, 'gender' => 'Male', 'role' => 'student', 'department' => 'CCS',
                'program' => 'Bachelor of Science in Information Technology', 'year_level' => '3rd Year', 'phone_number' => '09180000050',
            ],
        ];

        $students = [$studentIra, $studentAllysa];
        foreach ($testStudentsData as $sData) {
            $student = User::updateOrCreate(
                ['email' => $sData['email']],
                array_merge($sData, [
                    'email_verified_at' => Carbon::now(),
                    'password'          => Hash::make('Student@123'),
                    'terms_accepted_at' => Carbon::now(),
                ])
            );
            $students[] = $student;
        }

        $this->command->info('Users seeded: ' . count($students) . ' students + 1 guidance officer.');

        // Clean previous test conversational and analytical data for an idempotent run
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        ChatMessage::truncate();
        Conversation::truncate();
        EmotionLog::truncate();
        CrisisAlert::truncate();
        MoodEntry::truncate();
        AdminNotification::truncate();
        SessionLog::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 3. Populate Conversations & Chat Messages
        $conversationsData = [
            // ---- ORIGINAL CONVERSATIONS ----
            [
                'user'  => $studentIra,
                'title' => 'Managing Capstone Deadlines & Anxiety',
                'messages' => [
                    [
                        'msg'     => 'Hi LeanOn Bot, I have been feeling extremely anxious about our upcoming capstone defense. I feel like I am letting my group down.',
                        'reply'   => "Hello Ira. It's completely valid to feel the weight of capstone deadlines—they can be genuinely intimidating. Remember that your worth isn't determined by a single deadline, and doing your best is what matters. Have you tried breaking down your tasks into small, manageable milestones today?",
                        'emotion' => 'anxious',
                    ],
                    [
                        'msg'     => 'I tried, but every time I sit down to code, my chest feels tight and I cannot focus.',
                        'reply'   => 'That physical sensation is a clear sign your nervous system is in overdrive. Let\'s pause together right now. Take a slow, deep breath in for 4 seconds, hold for 4, and exhale gently for 6. You don\'t have to finish everything today. What is just one tiny task you can do next?',
                        'emotion' => 'stressed',
                    ],
                    [
                        'msg'     => 'Thank you, that breathing exercise helped steady me a bit. I will just fix the database seeder first.',
                        'reply'   => 'That is a fantastic, concrete first step! Celebrate small wins. I am always right here whenever you need a breather.',
                        'emotion' => 'calm',
                    ],
                ],
            ],
            [
                'user'  => $studentAllysa,
                'title' => 'Feeling Burned Out and Exhausted',
                'messages' => [
                    [
                        'msg'     => 'I feel so drained lately. Even getting out of bed for my 7 AM classes feels impossible.',
                        'reply'   => 'Hi Allysa, thank you for sharing that with me. Academic burnout is very real, especially when you\'ve been pushing yourself hard without real rest. Have you been able to get proper sleep and nutrition recently?',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'Barely. I have back-to-back quizzes and assignments and I haven\'t taken a day off in weeks.',
                        'reply'   => 'Your body and mind are asking for kindness. Please remember that taking rest is not slacking off—it\'s necessary maintenance. Even 15 minutes away from screens to listen to music or walk can help reset your mental energy.',
                        'emotion' => 'stressed',
                    ],
                ],
            ],
            [
                'user'  => $students[2], // Mark Anthony Santos
                'title' => 'Struggling with Algorithms and Impostor Syndrome',
                'messages' => [
                    [
                        'msg'     => 'Everyone in my programming class seems to get Data Structures so quickly, and I am falling behind.',
                        'reply'   => 'Impostor syndrome is so common in tech courses, Mark. People often showcase their confidence while hiding their own struggles. Everyone learns at a different pace, and understanding concepts deeply takes time.',
                        'emotion' => 'anxious',
                    ],
                    [
                        'msg'     => 'Yeah, I guess you\'re right. I will ask my professor for clarification during office hours.',
                        'reply'   => 'That is an excellent proactive decision! Seeking help is a strength, not a weakness. Good luck, you\'ve got this!',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
            [
                'user'  => $students[3], // Bea Nicole Ramos
                'title' => 'Financial Stress and Part-time Work',
                'messages' => [
                    [
                        'msg'     => 'Balancing working part-time and studying for finals is overwhelming me.',
                        'reply'   => 'Balancing a job and university studies is a tremendous accomplishment in itself, Bea. It shows great resilience, but it\'s also very demanding. Let\'s look at ways you can protect your wellbeing during exam week.',
                        'emotion' => 'overwhelmed',
                    ],
                ],
            ],

            // ---- NEW TAGALOG/ENGLISH CONVERSATIONS ----

            // CCS Students
            [
                'user'  => $students[6], // Rafael Dela Cruz
                'title' => 'Pagod na sa Programming at Walang Tulog',
                'messages' => [
                    [
                        'msg'     => 'Boss, 3 araw na akong hindi natutulog dahil sa project namin sa OOP. Hindi ko na alam kung kakayanin ko pa.',
                        'reply'   => 'Hoy Rafael, naririnig kita. Tatlong araw na walang tulog — ang katawan mo ay nagdadala ng malaking bigat. Ang pagod ay hindi kahinaan, ito ay senyales na kailangan mo ng pahinga. Subukan mo munang matulog nang kahit 2 hanggang 3 oras bago mo ituloy. Pagkagising mo, mas malinaw na ang isip mo.',
                        'emotion' => 'stressed',
                    ],
                    [
                        'msg'     => 'Pero deadline bukas. Paano kung hindi ko matapos?',
                        'reply'   => 'I understand the pressure feels immense right now. Let\'s be practical: what is the minimum viable version of your project that you can submit? Focus on that core functionality first. A submitted imperfect project is better than a perfect one never turned in.',
                        'emotion' => 'anxious',
                    ],
                    [
                        'msg'     => 'Okay, susukuran ko muna ang pinaka-importante. Salamat, kailangan ko ng mariig na reminder.',
                        'reply'   => 'Kaya mo yan, Rafael! Isa-isa lang. At kapag natapos mo na ang project, bigyan mo ang sarili mo ng maayos na pahinga. Proud sa yo na nagpatuloy ka kahit pagod.',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
            [
                'user'  => $students[10], // Patrick James Lim
                'title' => 'Cybersecurity Exam Pressure',
                'messages' => [
                    [
                        'msg'     => 'I have a major cybersecurity midterm tomorrow and I feel like I know nothing. I studied for hours but nothing is sticking.',
                        'reply'   => 'That feeling of "nothing sticking" is a sign of cognitive overload, Patrick — your brain has simply absorbed a lot and needs a reset. Let\'s try active recall: instead of re-reading your notes, close them and write down everything you remember. What topics feel most shaky to you right now?',
                        'emotion' => 'anxious',
                    ],
                    [
                        'msg'     => 'Cryptography and network protocols. The formulas are killing me.',
                        'reply'   => 'Cryptography formulas are notoriously dense. Try mnemonics or draw simple diagrams linking concepts together. Remember, understanding the WHY behind each protocol often helps more than memorizing the HOW. You\'ve put in the hours — trust your preparation tonight.',
                        'emotion' => 'stressed',
                    ],
                    [
                        'msg'     => 'I\'ll try the active recall method. Thanks, I feel a bit less panicked.',
                        'reply'   => 'That\'s the spirit! Get a good sleep tonight — memory consolidation happens during rest. You\'ll do great tomorrow!',
                        'emotion' => 'calm',
                    ],
                ],
            ],
            [
                'user'  => $students[12], // Renz Michael Ocampo
                'title' => 'Thesis Writing Block at Pressure ng Pamilya',
                'messages' => [
                    [
                        'msg'     => 'Hindi ako makapag-focus sa thesis ko kasi lagi na lang nagagalit ang mama ko na may trabaho pa rin akong ginagawa habang nagaaral.',
                        'reply'   => 'Naiintindihan ko kung gaano kahirap ang magbalanse ng pamilya, trabaho, at pag-aaral nang sabay-sabay, Renz. Ang presyur ng magulang ay nagmumula sa pagmamahal, ngunit maaari rin itong maging nakakapreno. Maaari ba nating pag-usapan kung paano mo mahahawakan ang mga iyon nang paisa-isa?',
                        'emotion' => 'overwhelmed',
                    ],
                    [
                        'msg'     => 'Gusto kong makatapos ng thesis pero parang lahat ng bagay ay humahadlang. Minsan gusto ko na lang sumuko.',
                        'reply'   => 'Ang pakiramdam na gusto nang sumuko ay natural kapag hina-handle mo ang maraming bagay. Pero tandaan mo: ang nararamdaman mong exhaustion ay hindi ebidensya na hindi ka kaya. Subukan nating gawing mas maliit ang iyong layunin ngayon — kahit isang paragraph lang ng thesis ang maibigay mo ngayon, progress na iyon.',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'Tama ka. Magsusulat muna ako ng introduction nang kahit dalawang pangungusap.',
                        'reply'   => 'Iyon ang tamang mindset! Dalawang pangungusap ngayon, isa pa bukas — ganyan nagsisimula ang mga malalaking bagay. Ipagpatuloy mo lang, at huwag kalimutang pag-usapan ang sitwasyon mo sa mama mo nang tahimik.',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
            [
                'user'  => $students[14], // Nathaniel Castro
                'title' => 'Graduation Pressure at Future Uncertainty',
                'messages' => [
                    [
                        'msg'     => 'I\'m graduating soon but I have no idea what I want to do after. Everyone expects me to get a high-paying IT job but I want to travel and explore first.',
                        'reply'   => 'It\'s completely valid to feel torn between external expectations and your own desires, Nathaniel. Post-graduation anxiety is real and extremely common among graduating students. Your feelings of wanting to explore are not immature — they\'re part of your identity. What does your ideal first year after graduation look like to you, beyond what others expect?',
                        'emotion' => 'anxious',
                    ],
                    [
                        'msg'     => 'I just want a few months to breathe. But I feel so guilty about it.',
                        'reply'   => 'Guilt often comes from internalized pressure, not from any actual wrongdoing. Taking intentional time to rest and reset after years of rigorous academic work is not irresponsibility — it\'s wisdom. Perhaps you could set a soft timeline for yourself: explore freely for a defined period, then commit to your career path with a clear head.',
                        'emotion' => 'sad',
                    ],
                ],
            ],
            [
                'user'  => $students[18], // Aldrich Manalo
                'title' => 'First Year University Adjustment Blues',
                'messages' => [
                    [
                        'msg'     => 'Malayo ang bahay ko at ito ang unang beses na lumayo ako sa pamilya ko. Sobrang homesick ako at hindi ko alam kung kaya ko ang kolehiyo.',
                        'reply'   => 'Helo Aldrich! Ang homesickness ay isa sa mga pinaka-common na nararamdaman ng mga bagong estudyante. Mahal mo ang pamilya mo — at iyon ay magandang bagay. Ngunit tandaan na ang pagiging malayo sa kanila ay pansamantala lamang, at ang bawat araw na nakakaraos ka ay nagpapatunay ng iyong lakas.',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'Minsan gusto ko na lang umuwi at hindi na bumalik.',
                        'reply'   => 'Narinig kita, at ang pakiramdam na iyon ay totoo at tunay. Pero bago ka gumawa ng malalaking desisyon, subukan mo munang makipag-usap sa iyong mga blockmates o sumali sa isang club. Ang pakikipagkilala sa mga bagong tao ay madalas na nagbabago ng lahat. Nakaramdam ka na ba ng kahit isang sandaling masaya dito sa kolehiyo?',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'Minsan kapag nagsasalita kami ng blockmates ko. Okay sila.',
                        'reply'   => 'Iyon ang pinakaimportanteng hakbang — ang paghanap ng koneksyon! Palakasin mo ang friendship na iyon. Ang kolehiyo ay hindi lamang tungkol sa academics; ito rin ay tungkol sa pagiging bahagi ng isang komunidad. Ikaw ay nasa tamang daan na.',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
            [
                'user'  => $students[22], // Lance Gabriel Miranda
                'title' => 'Senior Year Burnout at Job Hunting',
                'messages' => [
                    [
                        'msg'     => 'I\'ve been sending resumes for 3 months with no response. It\'s really discouraging. I question if my IT degree is even worth anything.',
                        'reply'   => 'Job hunting, especially right after graduation, can be an incredibly discouraging experience — and your frustration is completely understandable, Lance. The job market can be slow, but your degree and the skills you\'ve built are real and valuable. Can you tell me more about what roles you\'ve been applying for?',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'Mostly software developer roles. My portfolio only has college projects and I feel like I\'m competing against people with years of experience.',
                        'reply'   => 'Here\'s the truth: every experienced developer started with only college projects on their portfolio. Consider contributing to open-source projects, building a personal project that solves a real problem, or applying to internships to gain industry exposure. Even one concrete project can set you apart. You\'re not behind — you\'re just at the beginning of the race.',
                        'emotion' => 'anxious',
                    ],
                    [
                        'msg'     => 'I never thought about open source. I\'ll look into that. Thanks for the perspective.',
                        'reply'   => 'That shift in perspective is everything! Start small — look for "good first issue" tags on GitHub. Every contribution counts. I\'m rooting for you, Lance!',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],

            // CAHS Students
            [
                'user'  => $students[7], // Sophia Mae Garcia
                'title' => 'Nursing Board Exam Fear at Burnout',
                'messages' => [
                    [
                        'msg'     => 'Takot na takot ako sa board exam. Lahat ng aking mga kakilala ay pumasa na noong unang take nila at ako pa rin ay naghahanda.',
                        'reply'   => 'Sophia, ang takot na nararamdaman mo ay nagpapakita kung gaano mo ito pinapahalagahan. Ngunit huwag mong gamitin ang ibang tao bilang sukatan ng iyong sariling paglalakbay — ang bawat tao ay may sariling timeline. Ang mahalaga ay ikaw ay naghahanda. Anong parte ng exam ang pinaka-nakaka-anxiety sa iyo ngayon?',
                        'emotion' => 'anxious',
                    ],
                    [
                        'msg'     => 'Yung pharmacology. Napakaraming drugs na kailangan kong memorize at lagi akong nakakalimot.',
                        'reply'   => 'Pharmacology is one of the most memory-intensive parts of the nursing boards — you\'re not alone in finding it overwhelming. Try grouping drugs by classification and their common suffix patterns (e.g., -olol for beta blockers). Use flashcard apps like Anki for spaced repetition. Reviewing 20 drug cards daily beats trying to cram hundreds at once.',
                        'emotion' => 'stressed',
                    ],
                    [
                        'msg'     => 'Susubukan ko ang Anki. Hindi ko pa yan na-try pero mukhang maganda ang approach na iyon.',
                        'reply'   => 'Ikaw ay patungo na sa tamang direksyon, Sophia! Bigyan mo ang sarili mo ng credit sa tiyaga na iyong ipinakita. Kaya mo ang board exam na ito.',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
            [
                'user'  => $students[16], // Trisha Marie Guevarra
                'title' => 'Clinical Duty Stress and Patient Loss',
                'messages' => [
                    [
                        'msg'     => 'I witnessed a patient die during my clinical duty yesterday and I can\'t stop replaying it in my mind. I don\'t think I\'m cut out to be a nurse.',
                        'reply'   => 'Trisha, what you experienced is deeply impactful. Witnessing a patient pass is one of the most emotionally challenging experiences in healthcare, and it is completely normal to feel shaken, sad, or even questioning your path afterward. This reaction doesn\'t mean you\'re not cut out for nursing — it means you have profound human empathy, which is the heart of great nursing care.',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'I keep thinking about what I could have done differently. Maybe I missed something.',
                        'reply'   => 'That tendency to replay and second-guess is called "rumination" and it\'s very common after traumatic events. Unless there was something clinically actionable, you must remember that you did what was within your role and capacity. Please talk to your clinical instructor or a counselor about this — processing grief with support makes a real difference.',
                        'emotion' => 'sad',
                    ],
                ],
            ],
            [
                'user'  => $students[27], // Samantha Lozano
                'title' => 'Overloaded with Hospital Duties and Academics',
                'messages' => [
                    [
                        'msg'     => 'Halos araw-araw akong nasa hospital for clinical duties tapos babalik pa akong magaral at mag-aral ng lectures. Wala na akong oras para sa sarili ko.',
                        'reply'   => 'Ang setup na iyan ay tunay na nakaka-pagod, Samantha. Ang nursing students ay kinikilala bilang isa sa mga pinaka-heavily loaded na estudyante at may matibay na dahilan doon. Ang kawalan ng oras para sa sarili ay maaaring humantong sa burnout kung hindi ito aalugin. Mayroon bang kahit 20 minuto sa isang araw na maaari mong ibigay para sa sarili mo?',
                        'emotion' => 'overwhelmed',
                    ],
                    [
                        'msg'     => 'Wala talaga. Kahit sa pagkain ay madalas akong nakakalimot.',
                        'reply'   => 'Iyon ay isang senyales na ang iyong pisikal na pangangailangan ay napapabayaan na rin. Ang pagkain ay hindi opsyonal — ito ang gasolina ng iyong katawan at isip. Subukan nating mag-isip ng isang maliliit na bagay na maaari mong gawin: kahit 10-minuto lunch break na walang paghahanap ng notes. Ikaw ay importanteng tao, hindi lang isang estudyante.',
                        'emotion' => 'stressed',
                    ],
                    [
                        'msg'     => 'Minsan pakiramdam ko isa lang akong makina. Nakakalungkot.',
                        'reply'   => 'Hindi ka makina, Samantha — ikaw ay tao. Ang nararamdaman mong kalungkutan ay isang tawag ng iyong sarili na mag-stop at magpalinga. Pag-isipan mo ang pakikipag-usap sa iyong adviser o sa guidance office tungkol sa load management. Hindi kahinaan ang humingi ng tulong — ito ay katalinuhan.',
                        'emotion' => 'sad',
                    ],
                ],
            ],

            // CBA Students
            [
                'user'  => $students[8], // Juan Carlo Reyes
                'title' => 'Accountancy Board Review Stress',
                'messages' => [
                    [
                        'msg'     => 'My review for the CPA board is consuming my entire life. I haven\'t seen my family in weeks and I feel like I\'m losing my sense of self.',
                        'reply'   => 'The CPA board review is an immense undertaking, Juan Carlo, and what you\'re feeling — the isolation and the loss of personal identity — are very common side effects of intensive review programs. Your wellbeing matters just as much as your exam results. When was the last time you did something purely for enjoyment?',
                        'emotion' => 'overwhelmed',
                    ],
                    [
                        'msg'     => 'I honestly can\'t remember. Maybe months ago. I used to play guitar but I haven\'t touched it since review started.',
                        'reply'   => 'Your guitar hasn\'t gone anywhere — it\'s waiting for you. Consider scheduling even 15 minutes of playing once a week. Hobbies aren\'t a distraction from your goal; they\'re fuel that prevents burnout and actually improves retention and cognitive performance. You are more than your exam results.',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'Maybe I\'ll play a little tonight. I miss it.',
                        'reply'   => 'Yes! Please do. That small act of reconnecting with something you love is powerful self-care. You deserve joy, Juan Carlo, not just hard work.',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
            [
                'user'  => $students[11], // Alyssa Nicole Bautista
                'title' => 'Group Project Conflict at Pagod sa Pag-aaral',
                'messages' => [
                    [
                        'msg'     => 'Hindi maayos ang grupo namin. Dalawa sa amin ang gumagawa ng lahat habang ang iba ay walang ginagawa. Galit na galit na ako.',
                        'reply'   => 'Naiintindihan ko ang iyong frustrasyon, Alyssa. Ang unfair na distribution ng trabaho sa loob ng isang grupo ay isa sa mga pinakakaraniwang pinagmulan ng stress para sa mga estudyante. Ang iyong galit ay lehitimo. Pero bago mo ito itagal, subukan nating pag-usapan kung paano mo ito malulutas nang maayos.',
                        'emotion' => 'angry',
                    ],
                    [
                        'msg'     => 'Sinubukan ko nang kausapin sila pero wala pa rin silang ginagawa. Gusto ko na lang itong isumite kahit incomplete.',
                        'reply'   => 'Bago ka sumuko sa grupo, isaalang-alang mo ang pag-usap ng sitwasyon sa inyong professor. Hindi ito pagtuturo o pag-iingit — ito ay pagiging responsable para sa iyong sariling marka at pagsisikap. Maaari ring gumawa ng documentation ng inyong mga contribution bilang proteksyon.',
                        'emotion' => 'stressed',
                    ],
                ],
            ],
            [
                'user'  => $students[17], // Carl Angelo Perez (CEAS)
                'title' => 'Pagiging Guro at Pag-aalala sa Kinabukasan',
                'messages' => [
                    [
                        'msg'     => 'Gusto ko talagang maging guro pero nakakatakot dahil mababa ang sweldo. Lagi akong binibiro ng pamilya ko na maghanap ng ibang trabaho.',
                        'reply'   => 'Ang pagpili ng career na batay sa passion mo ay isa sa mga pinaka-courageous na desisyon na magagawa ng isang tao, Carl Angelo. Ang mga salitang narinig mo mula sa pamilya, kahit nagmumula sa pagmamahal, ay maaaring makasakit at makapag-doubt sa iyo. Alam mo ba kung bakit mo gustong maging guro?',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'Gusto ko dahil may epekto ako sa mga bata. Noong highschool, ang isang guro ang nagbago ng aking buhay.',
                        'reply'   => 'Iyan ang pinaka-makapangyarihang dahilan. Ang isang guro na natutuya ang kanyang tungkulin ay gumagawa ng ripple effects na naabot ang maraming henerasyon. Ang sahod ay maaaring maging limitado sa simula, ngunit ang epekto mo sa mundo ay hindi matatantyahan ng pera. Ipagpatuloy mo ang iyong pangarap.',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],

            // CEAS Students
            [
                'user'  => $students[9], // Maria Luisa Villanueva
                'title' => 'Student Teaching Anxiety at Peer Pressure',
                'messages' => [
                    [
                        'msg'     => 'Bukas ay ang aking unang student teaching demo at hindi ako makatulog sa kaba. Parang mababawi ko lahat ng nilipas na taon ng pag-aaral bukas.',
                        'reply'   => 'Ang kaba bago ang isang malaking demo ay natural at maganda — ibig sabihin ay sineseryoso mo ito. Isipin mo na ang mga bata na iyong itaturo bukas ay hindi naghahanap ng perpektong guro; naghahanap sila ng isang taong tunay at mapagmalasakit. Handa ka na — naghanda ka nang matagal para sa sandaling ito.',
                        'emotion' => 'anxious',
                    ],
                    [
                        'msg'     => 'Pero paano kung malimutan ko ang aking lesson plan o magtanong ang mga bata ng hindi ko masasagot?',
                        'reply'   => '"Hindi ko alam ngayon pero alamin natin" — yan ang sagot ng isang napakagandang guro. Ang pagiging honest sa mga estudyante kapag hindi mo alam ang sagot ay nagtuturo sa kanila ng mas mahalagang aralin kaysa sa anumang curriculum. Bring your lesson plan as a guide, not a script.',
                        'emotion' => 'anxious',
                    ],
                    [
                        'msg'     => 'Parang mas naka-relax na ako ngayon. Salamat. Matutulog na ako nang mas maaga.',
                        'reply'   => 'Iyon ang pinakamahalagang bagay na magagawa mo ngayon — magpahinga. Bukas, huminga nang malalim bago ka pumasok sa silid-aralan at ipaalala sa sarili mo: ikaw ay sapat, handa ka, at kaya mo ito!',
                        'emotion' => 'calm',
                    ],
                ],
            ],
            [
                'user'  => $students[20], // Elijah Reyes (CEAS)
                'title' => 'Low Grades at Self-Doubt',
                'messages' => [
                    [
                        'msg'     => 'I failed my major subject exam and now I\'m worried I might not graduate on time. I feel so stupid.',
                        'reply'   => 'Elijah, failing one exam does not define your intelligence or your future. It\'s a setback, not a verdict. Many successful people have failed exams — what matters is what you do next. Can you tell me what happened during your preparation for this exam?',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'I wasn\'t able to study well because my mother was sick and I was taking care of her.',
                        'reply'   => 'That context changes everything. You were carrying an enormous responsibility on top of your academics — that is not a sign of being "stupid," that is being human. Please talk to your professor about your situation. Professors are generally understanding of valid family emergencies. You deserve support, not judgment.',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'I didn\'t think about telling my professor. I was too embarrassed.',
                        'reply'   => 'There is absolutely nothing to be embarrassed about. Caring for a sick parent is one of the most noble things a person can do. Reach out today — the sooner you communicate, the more options you may have. I believe in you, Elijah.',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
            [
                'user'  => $students[34], // Bianca Tolentino (CEAS)
                'title' => 'Pagod sa Pagbabasa at Wala na sa Mood',
                'messages' => [
                    [
                        'msg'     => 'Mga ilang linggo na akong walang ganang mag-aral. Binubuksan ko yung libro pero hindi pa rin nakakatuon. Parang nawala na ang aking motivation.',
                        'reply'   => 'Ang pagkawala ng motivation, lalo na sa gitna ng semestre, ay isang karanasang tinatawag ng mga psychologist na "motivational dip." Hindi ito katamaran — ito ay isang cycle na natural na nangyayari. Bianca, ano ang nakakaramdam mong masaya o excited bago ka dumating sa kolehiyo? Anong mga bagay ang ginaganahan mo noon?',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'Mahilig akong magsulat ng mga tula at kwento. Pero wala na akong oras doon.',
                        'reply'   => 'Ang pagsusulat ay hindi lang isang libangan — ito ay isang mahalagang bahagi ng kung sino ka. Subukang ilaan ang 10 minuto bago matulog para magsulat ng kahit isang linya ng tula. Hindi ito pag-aaksaya ng oras — ito ang pagpapakain sa iyong kaluluwa na magtutulak sa iyo pabalik sa iyong mga pag-aaral.',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'Masubukan nga. Matagal na akong hindi nagsusulat. Salamat.',
                        'reply'   => 'Balitaan mo ako kung anong nangyari! Lagi kang maligayang bumalik dito, Bianca. Ang iyong kwento ay hindi pa tapos.',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],

            // More mixed conversations
            [
                'user'  => $students[21], // Camille Rose Domingo (CBA)
                'title' => 'Marketing Internship Rejection Blues',
                'messages' => [
                    [
                        'msg'     => 'I got rejected from the internship I really wanted at a big marketing firm. I worked so hard on my application and now I just feel worthless.',
                        'reply'   => 'That rejection stings, Camille — especially when you put your heart into an application. Rejection from a competitive position doesn\'t reflect your worth; it reflects a single company\'s fit at a specific moment. The skills you built preparing that application are still yours. How are you holding up emotionally right now?',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'I keep refreshing their email hoping it was a mistake. I really thought I did well in the interview too.',
                        'reply'   => 'The "refresh loop" after rejection is so human — it shows how much this mattered to you. Since you felt good about the interview, consider emailing them a brief thank-you and asking for feedback. That information is gold for future applications, and it shows remarkable maturity. Many interviewers genuinely respect that.',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'That\'s actually a good idea. I\'ll draft an email tomorrow.',
                        'reply'   => 'That\'s growth! One rejection is just a redirect. Apply to three more places — your right fit is out there. Keep going, Camille.',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
            [
                'user'  => $students[29], // Patricia Anne Bernardo (CBA)
                'title' => 'Stress sa Accounting at Pressure ng Pamilya',
                'messages' => [
                    [
                        'msg'     => 'Gusto ng magulang ko na maging CPA accountant ako kahit na ang gusto ko ay sa entrepreneurship. Ayaw nilang marinig ang gusto ko.',
                        'reply'   => 'Ang sitwasyong ito — ang pagitan ng inaasahan ng magulang at ng iyong sariling pangarap — ay isa sa mga pinaka-emotional na hamon na haharapin ng mga kabataan. Patricia, ang iyong mga pangarap ay totoo at mahalaga. Maaari mo bang sabihin sa akin nang mas malalim kung bakit mo gusto ang entrepreneurship?',
                        'emotion' => 'anxious',
                    ],
                    [
                        'msg'     => 'Gusto ko magtayo ng sariling negosyo someday. Mas masaya akong mag-isip ng mga business ideas kaysa mag-reconcile ng accounts.',
                        'reply'   => 'Ito ay magandang insight tungkol sa iyong sarili. At narito ang magandang balita: ang pag-aaral ng accountancy ay isang napakalakas na pundasyon para sa entrepreneurship. Maraming matagumpay na negosyante ang may background sa accounting dahil nauunawaan nila ang financial statements at cash flow. Maaaring hindi ito "ito o iyon" — maaaring ito ay "ito at iyon."',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
            [
                'user'  => $students[31], // Nico Rafael Abad (CCS)
                'title' => 'Social Anxiety at Mahirap Makipagkaibigan',
                'messages' => [
                    [
                        'msg'     => 'Mahirap para sa akin ang makipag-usap sa mga bagong tao. Introvert ako at lagi na lang akong nag-iisa sa klase. Hindi ko alam kung normal ba ito.',
                        'reply'   => 'Hello Nico! Una sa lahat — oo, normal iyon. Ang pagiging introvert ay hindi isang problema na kailangang ayusin. Ngunit kung ang pagiging nag-iisa ay nagbibigay sa iyo ng kalungkutan sa halip na kapayapaan, maaaring sulit na tuklasin ang mga paraan para makapag-connect sa mga tao sa mas komportableng paraan para sa iyo.',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'Gusto ko naman talagang magkaroon ng mga kaibigan, pero kapag nagsasalita ako ay parang mali ang lahat ng sasabihin ko.',
                        'reply'   => 'Ang takot na iyon — ng pagiging "mali" — ay tinatawag na social anxiety, at ito ay isang karanasang maraming tao ang nagdadala nang tahimik. Narito ang isang maliit na hamon: subukan mo ngayong araw na mag-kumusta sa isang katabi mo sa klase. Isang linya lang. Hindi kailangan na maging perpekto — tanging tunay.',
                        'emotion' => 'anxious',
                    ],
                    [
                        'msg'     => 'Mahirap pero susubukan ko. Salamat sa pag-unawa.',
                        'reply'   => 'Ikaw ay mas matapang kaysa sa iyong naiisip, Nico. At palagi kang tinatanggap dito kapag kailangan mong makipag-usap.',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
            [
                'user'  => $students[36], // Dominic Vergara (CCS)
                'title' => 'Capstone Defense at Imposter Syndrome',
                'messages' => [
                    [
                        'msg'     => 'My capstone panel destroyed my presentation yesterday. I worked on it for months and they tore it apart. I want to drop the subject.',
                        'reply'   => 'That sounds incredibly deflating, Dominic — months of hard work met with harsh critique is genuinely painful. But let me ask: did the panel say your project was fundamentally flawed, or did they question specific areas for improvement? Those are very different things.',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'They mainly questioned my methodology and the scope of my system. They said it\'s too ambitious.',
                        'reply'   => 'That\'s actually valuable feedback — "too ambitious" means your vision is there, but your execution scope needs focus. Every great system starts with a smaller MVP. This is not a failure; it\'s calibration. Work with your adviser to identify the core features that will make your project defensible and impactful. You\'re closer than you think.',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'I\'ll meet with my adviser this week. I just needed to hear that it\'s not over.',
                        'reply'   => 'It is absolutely not over. Redefining scope is a normal part of research development. The fact that you\'re still willing to push forward shows true dedication. Go get that revised proposal done!',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
            [
                'user'  => $students[25], // Ian Gabriel Corpuz (CEAS)
                'title' => 'Relationship Issues na Nakakaapekto sa Pag-aaral',
                'messages' => [
                    [
                        'msg'     => 'Naghiwalay kami ng girlfriend ko ng isang linggo na ang nakakaraan at hindi ko mapigilan ang mag-isip sa kanya. Nakakaapekto na sa school performance ko.',
                        'reply'   => 'Ang breakup ay isa sa mga pinakamasakit na karanasan ng tao, Ian. Ang kalungkutan, confusion, at kawalan ng focus na nararamdaman mo ay lahat ng normal na bahagi ng pag-heal. Mahalaga na kilalanin mo ang nararamdaman mo nang hindi mo ito binabalewala. Kumusta ka ngayon, tunay na kumusta ka?',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'Hindi ko alam. Parang may butas sa dibdib ko. Hindi ako makakaen ng maayos.',
                        'reply'   => 'Iyon ang tinatawag na heartbreak — at hindi ito metaporikal lamang; may aktwal na physiological response ang katawan sa emotional pain. Pero pakinggan mo ito: ang butas na iyon ay hindi permanente. Ang pagkain ay kritikal — kahit maliit na bagay, banana o isang tasa ng sopas. Ang iyong katawan ay kailangan ang sustansya para kayahin ang emosyon.',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'Kakain na nga ako. Matagal na pala akong hindi kumakain ng maayos. Salamat.',
                        'reply'   => 'Iyon ang tamang hakbang. Isa-isang araw lang muna. At kapag gusto mong makipag-usap ulit, nandito lang ako.',
                        'emotion' => 'calm',
                    ],
                ],
            ],
            [
                'user'  => $students[32], // Gian Carlo Dela Rosa (CCS)
                'title' => 'Sleep Deprivation at Mental Health Decline',
                'messages' => [
                    [
                        'msg'     => 'I\'ve been averaging 3-4 hours of sleep for the past month because of coding projects. I feel like a zombie and I\'m starting to feel really dark thoughts.',
                        'reply'   => 'Gian Carlo, I want to make sure I understand what you mean by "dark thoughts." Can you tell me more about what those thoughts are like? I want to make sure you\'re safe.',
                        'emotion' => 'stressed',
                        'is_crisis' => true,
                    ],
                    [
                        'msg'     => 'Not like harming myself, just like... what\'s the point of all this. Like nothing matters anymore.',
                        'reply'   => 'Thank you for clarifying — that helps me understand better. What you\'re describing sounds like the emotional emptiness that comes with severe sleep deprivation and burnout. When our brain is this exhausted, it loses the ability to generate positive emotions temporarily. This is a real, physiological state. Please talk to someone at the guidance office this week — not because something is "wrong" with you, but because you deserve support right now.',
                        'emotion' => 'sad',
                    ],
                ],
            ],
            [
                'user'  => $students[40], // Ysabel Sison (CBA)
                'title' => 'Pag-aalala sa Grades at Scholarship',
                'messages' => [
                    [
                        'msg'     => 'Nasa panganib ang scholarship ko. Kailangan ko ng 1.75 GWA pero natatakot akong hindi ko makuha ngayong semestre dahil mahirap ang mga subjects.',
                        'reply'   => 'Naiintindihan ko ang presyur ng scholarship, Ysabel. Para sa maraming estudyante, ang scholarship ay hindi lang financial support — ito ang simbolo ng kanilang pagkakataon. Ang takot na mawala ito ay tunay at matindi. Bago tayo mag-panic, alamin muna natin: ilan pa ang natitira mong subjects na may final exam at ano ang kasalukuyan mong grades?',
                        'emotion' => 'anxious',
                    ],
                    [
                        'msg'     => 'May tatlo pang final exams. Sa dalawa ay sigurado akong kaya ko pero yung Accounting 2 ay talagang mahirap.',
                        'reply'   => 'Maganda iyan — dalawang subject ay nasa control mo. Para sa Accounting 2, may oras ka pa. Mayroon ka bang access sa study groups o tutors sa inyong kolehiyo? Pati na rin, subukang kausapin ang iyong professor para sa additional guidance — maraming guro ang willing na tumulong sa isang estudyante na nagpapakita ng dedikasyon.',
                        'emotion' => 'anxious',
                    ],
                    [
                        'msg'     => 'Sasali na ako sa study group ng block ko. Hindi ko pa kasi ginagawa iyon.',
                        'reply'   => 'Iyon ang tamang desisyon! Ang collaborative learning ay napatunayang mas epektibo para sa mahirap na subjects tulad ng accounting. Magtiwala ka — may oras pa at may kakayahan ka. Kaya mo ito, Ysabel!',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
            [
                'user'  => $students[42], // Brent Francis Ramirez (CAHS)
                'title' => 'Nursing RLE Pressure at Family Expectations',
                'messages' => [
                    [
                        'msg'     => 'All of my family are nurses and doctors. I feel like I\'m constantly being compared to them and like I\'m never good enough in clinical.',
                        'reply'   => 'Growing up in a family of medical professionals can create an invisible but very heavy benchmark, Brent. The comparisons, even when well-intentioned, can erode your confidence. Remember that you are not competing with your family — you\'re building your own story. What do you feel you do well in your clinical rotations?',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'I\'m good at connecting with patients. They always seem comfortable with me.',
                        'reply'   => 'That is one of the most essential and genuinely rare skills in healthcare — therapeutic communication and patient trust. Procedures and clinical skills can be taught and drilled. Genuine human connection is a gift. You are already bringing something profound to nursing. Own that.',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
            [
                'user'  => $students[43], // Thea Marie Villanueva (CEAS)
                'title' => 'Practicum Stress at Mentorship Issues',
                'messages' => [
                    [
                        'msg'     => 'Ang supervising teacher ko ay laging nagsisimula ng critique sa harap ng mga estudyante. Nahahiya ako at nawawalan na ng confidence.',
                        'reply'   => 'Ang paraan ng feedback na iyan ay professionally inappropriate at nakaka-apekto sa iyong dignity, Thea. Ang constructive criticism ay dapat ibigay nang pribado, hindi sa harap ng mga estudyante. Ang pakiramdam ng kahihiyan na nararamdaman mo ay wasto. Mayroon ka bang trusted professor o coordinator sa inyong practicum na maaari mong kausapin tungkol sa sitwasyong ito?',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'Wala pa akong kausap. Natatakot kasi na baka maapektuhan ang aking grade.',
                        'reply'   => 'Ang takot na iyon ay naiintindihan, at ito ay isang legitimate na concern. Subukan mong i-document ang mga insidente nang may petsa at detalye. Pagkatapos, maaari kang lumapit sa iyong school coordinator nang hindi direkta naming ini-implicate ang supervising teacher sa simula — sabihin mo lamang na ikaw ay naghahanap ng guidance tungkol sa kung paano ang tamang pagtanggap ng feedback. Ang iyong karapatan ay kailangang pangalagaan.',
                        'emotion' => 'anxious',
                    ],
                ],
            ],
            [
                'user'  => $students[44], // Rome Andrei Navarro (CCS)
                'title' => 'Final Year System Development at Mental Exhaustion',
                'messages' => [
                    [
                        'msg'     => 'I\'m developing a full-stack system for my thesis and every time I fix one bug, three more appear. I\'m so mentally exhausted I can barely think.',
                        'reply'   => 'The bug waterfall is a universal rite of passage in software development — even professional developers deal with it daily. The fact that you\'re still pushing through shows real tenacity, Rome. Mental exhaustion in coding often means it\'s time for a break, not more hours. When did you last step away from the screen for more than 30 minutes?',
                        'emotion' => 'stressed',
                    ],
                    [
                        'msg'     => 'Maybe 3 days ago? I eat in front of my laptop and sleep whenever I crash.',
                        'reply'   => 'That level of tunnel focus is unsustainable and is actually counterproductive. Studies show that regular breaks — even walking for 10 minutes — significantly improve debugging effectiveness and creative problem solving. Please step away from your screen now for at least 30 minutes. The bugs will still be there, but you\'ll see them with fresh eyes.',
                        'emotion' => 'stressed',
                    ],
                    [
                        'msg'     => 'Fine, I\'ll take a walk. Thanks for the reality check.',
                        'reply'   => 'Come back refreshed! Sometimes the solution appears the moment you stop forcing it. You\'ve got this, Rome.',
                        'emotion' => 'calm',
                    ],
                ],
            ],
            [
                'user'  => $students[45], // Mia Clarice Hernandez (CBA)
                'title' => 'Post-Graduation Job Anxiety',
                'messages' => [
                    [
                        'msg'     => 'I graduated 2 months ago with honors and I still haven\'t found a job. People keep asking and I feel ashamed every time.',
                        'reply'   => 'Graduating with honors is a significant achievement, Mia — and the fact that you\'re job-hunting is already showing initiative. Two months in the job market, especially in a competitive economy, is not a long time. The shame you feel from others\' questions is understandable, but their timeline for your success is not your responsibility to meet.',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'I know logically, but emotionally it feels like failure.',
                        'reply'   => 'The gap between knowing something logically and feeling it emotionally is very real. What you\'re experiencing is normal grief over the gap between expectation and reality. Let\'s reframe: you are a fresh graduate who is actively building your next chapter. That is not failure — that is being human in transition.',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'I needed to hear that. I\'ve been too hard on myself.',
                        'reply'   => 'You have every right to celebrate your achievements and give yourself grace during this transition. Keep applying, keep growing, and remember: the right opportunity is looking for someone exactly like you.',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
            [
                'user'  => $students[47], // Kurt Ian Magno (CEAS)
                'title' => 'Athletic Performance at Academic Balance',
                'messages' => [
                    [
                        'msg'     => 'Athlete ako sa varsity pero kailangan ko ring panatilihin ang grades ko. Minsan pakiramdam ko pipiliin ko na lang ang isa sa dalawa.',
                        'reply'   => 'Ang buhay ng student-athlete ay nangangailangan ng exceptional time management at resilience, Kurt. Ang pakiramdam na kailangan mong pumili ay maaaring senyales na ang iyong current system ay kailangan ng adjustment, hindi ang iyong mga pangarap. Paano mo ngayon hinihiwalay ang iyong oras para sa training at pag-aaral?',
                        'emotion' => 'overwhelmed',
                    ],
                    [
                        'msg'     => 'Wala pa akong maayos na schedule. Practice tuwing umaga then pag-uwi ko ay pagod na ako para mag-aral.',
                        'reply'   => 'Iyon ang pangunahing isyu — ang pagod pagkatapos ng practice ay nagpapababa ng cognitive capacity para sa pag-aaral. Subukan nating baguhin ang approach: gumawa ng 30-minuto na "brain break" pagkatapos ng practice, pagkatapos ay mag-review nang kahit isang oras. Maaari ka ring mag-aral ng konti habang naghihintay sa practice — bawat minuto ay mahalaga.',
                        'emotion' => 'overwhelmed',
                    ],
                    [
                        'msg'     => 'Iyan ay mas realistic kaysa sa iniisip ko. Susubukan ko iyan.',
                        'reply'   => 'Ang susi ay consistency, hindi perfection. Kaya mo itong pagsabayin, Kurt — maraming champion na estudyante ang napatunayan na.',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
            [
                'user'  => $students[48], // Ella Rose Catalan (CBA)
                'title' => 'First Year Financial Struggles at Pag-aaral',
                'messages' => [
                    [
                        'msg'     => 'Hirap kaming mag-asawa ng aking magulang sa bayad ng tuition ko. Gusto ko nang mag-trabaho pero hindi ko rin masimulan ang klase.',
                        'reply'   => 'Ella, ang situasyong kinakaharap mo ay napaka-challenging — ang financial pressure sa pamilya habang ikaw ay nag-aaral ay isang malaking bigat para sa isang first-year student. Narito lang ako para sa iyo. Alam mo ba na ang inyong paaralan ay may scholarships, financial aid, o work-study programs na maaaring makatulong?',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'Hindi ko alam kung pwede pa ako mag-apply ng scholarship ngayong enrolled na ako.',
                        'reply'   => 'Maraming scholarships ang available kahit mid-year o sa susunod na semestre — at may mga espesyal na scholarship din para sa financially challenged students. Pumunta ka sa Scholarship Office ng inyong kolehiyo at itanong ang lahat ng available options. Huwag kang mahiyang itanong — iyan ang tamang lakad. Ikaw ay karapat-dapat sa tulong.',
                        'emotion' => 'anxious',
                    ],
                    [
                        'msg'     => 'Pupunta ako bukas. Salamat, hindi ko alam saan magsisimula.',
                        'reply'   => 'Magaling! At kapag nagkaroon ka ng updates, bumalik ka at ibahagi. Lagi kang tinatanggap dito, Ella. Ang iyong tapang na humingi ng tulong ay kagalang-galang.',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
            [
                'user'  => $students[49], // Zach Daniel Puzon (CCS)
                'title' => 'Career Confusion sa Programming',
                'messages' => [
                    [
                        'msg'     => 'I\'m in 3rd year IT but I\'m starting to question if I actually like coding. Everyone else seems so passionate about it and I just feel... meh.',
                        'reply'   => 'Mid-program doubt is actually more common than people admit, Zach. The question isn\'t really "do I like coding" — it\'s "which part of IT actually excites me?" Programming is just one of many directions in your field. Have you explored areas like UX design, IT project management, cybersecurity, or data analytics?',
                        'emotion' => 'anxious',
                    ],
                    [
                        'msg'     => 'Not really. I just assumed programming was the only path in IT.',
                        'reply'   => 'That\'s a very common misconception! IT is a massive ecosystem. Try taking a free short course in UX or data analytics online to see if anything sparks your interest. Passion is often discovered, not assumed. You have time to explore before you graduate.',
                        'emotion' => 'anxious',
                    ],
                    [
                        'msg'     => 'That\'s actually a relief. I thought something was wrong with me.',
                        'reply'   => 'Nothing is wrong with you — you\'re just still discovering yourself, which is exactly what your 20s are for. Explore freely, Zach. The right path will click.',
                        'emotion' => 'calm',
                    ],
                ],
            ],
        ];

        foreach ($conversationsData as $cIdx => $cData) {
            $user    = $cData['user'];
            $lastMsg = end($cData['messages'])['msg'];

            $conversation = Conversation::create([
                'user_id'      => $user->id,
                'email'        => $user->email,
                'title'        => $cData['title'],
                'last_message' => $lastMsg,
                'is_saved'     => ($cIdx % 3 === 0),
                'is_archived'  => ($cIdx % 7 === 0),
                'created_at'   => Carbon::now()->subDays(rand(1, 30)),
                'updated_at'   => Carbon::now()->subHours(rand(1, 48)),
            ]);

            foreach ($cData['messages'] as $mIdx => $m) {
                $msgTime  = Carbon::now()->subDays(rand(1, 14))->addMinutes($mIdx * 7);
                $isCrisis = $m['is_crisis'] ?? false;

                ChatMessage::create([
                    'user_id'         => $user->id,
                    'conversation_id' => $conversation->id,
                    'message'         => $m['msg'],
                    'reply'           => $m['reply'],
                    'is_crisis'       => $isCrisis,
                    'is_fallback'     => false,
                    'created_at'      => $msgTime,
                    'updated_at'      => $msgTime,
                ]);
            }

            // Create one emotion log per conversation
            $lastEmotion = end($cData['messages'])['emotion'] ?? 'neutral';
            EmotionLog::create([
                'user_id'         => $user->id,
                'conversation_id' => $conversation->id,
                'emotion'         => $lastEmotion,
                'created_at'      => Carbon::now()->subDays(rand(1, 7)),
                'updated_at'      => Carbon::now()->subDays(rand(1, 7)),
            ]);
        }

        // 4. Create Flagged Messages & Crisis Alerts for Testing
        $crisisStudent = $students[4]; // Christian David Cruz
        $crisisConv    = Conversation::create([
            'user_id'      => $crisisStudent->id,
            'email'        => $crisisStudent->email,
            'title'        => 'Urgent: Difficult Thoughts and Overwhelm',
            'last_message' => 'I feel completely hopeless and like I cannot keep doing this anymore. Everything is too much.',
            'is_saved'     => true,
            'is_archived'  => false,
            'created_at'   => Carbon::now()->subHours(12),
            'updated_at'   => Carbon::now()->subHours(2),
        ]);

        $crisisMsg = ChatMessage::create([
            'user_id'         => $crisisStudent->id,
            'conversation_id' => $crisisConv->id,
            'message'         => 'I feel completely hopeless and like I cannot keep doing this anymore. Everything is too much.',
            'reply'           => 'I hear how much pain and overwhelm you are feeling right now, and I want you to know that your life has immense value. You are not alone in this. Please connect with our Gordon College Guidance Center or reach out to someone you trust immediately.',
            'is_crisis'       => true,
            'is_fallback'     => false,
            'created_at'      => Carbon::now()->subHours(2),
            'updated_at'      => Carbon::now()->subHours(2),
        ]);

        EmotionLog::create([
            'user_id'         => $crisisStudent->id,
            'conversation_id' => $crisisConv->id,
            'emotion'         => 'hopeless',
            'created_at'      => Carbon::now()->subHours(2),
            'updated_at'      => Carbon::now()->subHours(2),
        ]);

        // Crisis Alert 1: Severe / Scheduled
        $alert1 = CrisisAlert::create([
            'user_id'              => $crisisStudent->id,
            'chat_message_id'      => $crisisMsg->id,
            'department'           => $crisisStudent->department,
            'gender'               => $crisisStudent->gender,
            'message'              => $crisisMsg->message,
            'severity'             => 'severe',
            'detected_keywords'    => ['hopeless', 'cannot keep doing this', 'too much'],
            'flag_reason'          => 'High crisis distress markers detected in conversation',
            'status'               => 'reviewed',
            'is_classified'        => true,
            'admin_email_sent_at'  => Carbon::now()->subHour(),
            'admin_email_notified' => true,
            'appointment_date'     => Carbon::tomorrow()->toDateString(),
            'appointment_time'     => '10:00 AM',
            'appointment_status'   => 'scheduled',
            'created_at'           => Carbon::now()->subHours(2),
            'updated_at'           => Carbon::now()->subHour(),
        ]);

        // Second severe alert for Christian — triggers "Urgent Wellness Check" (needs 2+ severe)
        $crisisConv2 = Conversation::create([
            'user_id'      => $crisisStudent->id,
            'email'        => $crisisStudent->email,
            'title'        => 'Follow-up: Thoughts of Giving Up',
            'last_message' => 'Ayoko na. Parang mas mabuti pa kung wala na ako.',
            'is_saved'     => true,
            'is_archived'  => false,
            'created_at'   => Carbon::now()->subDays(1)->subHours(4),
            'updated_at'   => Carbon::now()->subDays(1),
        ]);
        $crisisMsg2 = ChatMessage::create([
            'user_id'         => $crisisStudent->id,
            'conversation_id' => $crisisConv2->id,
            'message'         => 'Ayoko na. Parang mas mabuti pa kung wala na ako.',
            'reply'           => 'Naririnig ko ang hirap mo, Christian. Ang buhay mo ay mahalaga. Mangyaring makipag-ugnayan agad sa Gordon College Guidance Center o sa isang taong pinagkakatiwalaan mo.',
            'is_crisis'       => true,
            'is_fallback'     => false,
            'created_at'      => Carbon::now()->subDays(1),
            'updated_at'      => Carbon::now()->subDays(1),
        ]);
        EmotionLog::create([
            'user_id'         => $crisisStudent->id,
            'conversation_id' => $crisisConv2->id,
            'emotion'         => 'hopeless',
            'created_at'      => Carbon::now()->subDays(1),
            'updated_at'      => Carbon::now()->subDays(1),
        ]);
        $alert1b = CrisisAlert::create([
            'user_id'              => $crisisStudent->id,
            'chat_message_id'      => $crisisMsg2->id,
            'department'           => $crisisStudent->department,
            'gender'               => $crisisStudent->gender,
            'message'              => $crisisMsg2->message,
            'severity'             => 'severe',
            'detected_keywords'    => ['ayoko na', 'wala na ako'],
            'flag_reason'          => 'Repeated passive suicidal ideation across separate conversations',
            'status'               => 'reviewed',
            'is_classified'        => true,
            'admin_email_sent_at'  => Carbon::now()->subDays(1),
            'admin_email_notified' => true,
            'appointment_date'     => null,
            'appointment_time'     => null,
            'appointment_status'   => null,
            'created_at'           => Carbon::now()->subDays(1),
            'updated_at'           => Carbon::now()->subDays(1),
        ]);

        // Third severe alert for Christian — reinforces multi-severe urgent case
        $crisisConv3 = Conversation::create([
            'user_id'      => $crisisStudent->id,
            'email'        => $crisisStudent->email,
            'title'        => 'Night Crisis: Cannot Cope Anymore',
            'last_message' => 'I want to hurt myself. Nothing is helping and I feel empty.',
            'is_saved'     => false,
            'is_archived'  => false,
            'created_at'   => Carbon::now()->subHours(6),
            'updated_at'   => Carbon::now()->subHours(4),
        ]);
        $crisisMsg3 = ChatMessage::create([
            'user_id'         => $crisisStudent->id,
            'conversation_id' => $crisisConv3->id,
            'message'         => 'I want to hurt myself. Nothing is helping and I feel empty.',
            'reply'           => 'Thank you for telling me this — your safety comes first. Please reach out to the Guidance Office right away or call someone you trust. You do not have to face this alone.',
            'is_crisis'       => true,
            'is_fallback'     => false,
            'created_at'      => Carbon::now()->subHours(4),
            'updated_at'      => Carbon::now()->subHours(4),
        ]);
        EmotionLog::create([
            'user_id'         => $crisisStudent->id,
            'conversation_id' => $crisisConv3->id,
            'emotion'         => 'hopeless',
            'created_at'      => Carbon::now()->subHours(4),
            'updated_at'      => Carbon::now()->subHours(4),
        ]);
        $alert1c = CrisisAlert::create([
            'user_id'              => $crisisStudent->id,
            'chat_message_id'      => $crisisMsg3->id,
            'department'           => $crisisStudent->department,
            'gender'               => $crisisStudent->gender,
            'message'              => $crisisMsg3->message,
            'severity'             => 'severe',
            'detected_keywords'    => ['hurt myself', 'nothing is helping', 'empty'],
            'flag_reason'          => 'Active self-harm ideation detected; third severe classification for this student',
            'status'               => 'new',
            'is_classified'        => true,
            'admin_email_sent_at'  => null,
            'admin_email_notified' => false,
            'appointment_date'     => null,
            'appointment_time'     => null,
            'appointment_status'   => null,
            'created_at'           => Carbon::now()->subHours(4),
            'updated_at'           => Carbon::now()->subHours(4),
        ]);

        // Crisis Alert 2: Moderate / Unreviewed
        $alert2Student = $students[5]; // Danielle Joy Flores
        $alert2Conv    = Conversation::create([
            'user_id'      => $alert2Student->id,
            'email'        => $alert2Student->email,
            'title'        => 'Panic attack before practice teaching',
            'last_message' => 'I had a severe panic attack earlier and my heart won\'t stop racing.',
            'is_saved'     => false,
            'is_archived'  => false,
            'created_at'   => Carbon::now()->subHours(5),
            'updated_at'   => Carbon::now()->subHours(1),
        ]);

        $alert2Msg = ChatMessage::create([
            'user_id'         => $alert2Student->id,
            'conversation_id' => $alert2Conv->id,
            'message'         => 'I had a severe panic attack earlier and my heart won\'t stop racing.',
            'reply'           => 'Panic attacks can feel terrifying, but please remember you are safe. Let\'s focus on grounding yourself: look around and name 5 things you can see, 4 you can touch, and 3 you can hear.',
            'is_crisis'       => true,
            'is_fallback'     => false,
            'created_at'      => Carbon::now()->subHours(1),
            'updated_at'      => Carbon::now()->subHours(1),
        ]);

        $alert2 = CrisisAlert::create([
            'user_id'              => $alert2Student->id,
            'chat_message_id'      => $alert2Msg->id,
            'department'           => $alert2Student->department,
            'gender'               => $alert2Student->gender,
            'message'              => $alert2Msg->message,
            'severity'             => 'moderate',
            'detected_keywords'    => ['panic attack', 'racing'],
            'flag_reason'          => 'Severe acute anxiety symptoms reported',
            'status'               => 'new',
            'is_classified'        => true,
            'admin_email_sent_at'  => null,
            'admin_email_notified' => false,
            'appointment_date'     => null,
            'appointment_time'     => null,
            'appointment_status'   => null,
            'created_at'           => Carbon::now()->subHours(1),
            'updated_at'           => Carbon::now()->subHours(1),
        ]);

        // --- Additional Crisis Alerts from new students ---

        // Alert 3: Rafael Dela Cruz — Severe sleep deprivation with dark thoughts
        $alert3Student  = $students[6]; // Rafael Dela Cruz
        $alert3Conv     = Conversation::create([
            'user_id'      => $alert3Student->id,
            'email'        => $alert3Student->email,
            'title'        => 'Extreme Exhaustion at Masamang Pag-iisip',
            'last_message' => 'Minsan parang gusto ko na lang huminto sa lahat. Pagod na pagod na ako sa buhay.',
            'is_saved'     => true,
            'is_archived'  => false,
            'created_at'   => Carbon::now()->subHours(8),
            'updated_at'   => Carbon::now()->subHours(3),
        ]);
        $alert3Msg = ChatMessage::create([
            'user_id'         => $alert3Student->id,
            'conversation_id' => $alert3Conv->id,
            'message'         => 'Minsan parang gusto ko na lang huminto sa lahat. Pagod na pagod na ako sa buhay.',
            'reply'           => 'Naririnig kita nang malinaw, at ang sinabi mo ay sineseryoso ko nang lubos. Kapag sinasabi mong gusto mong huminto sa lahat — ibig sabihin ba nito ay mayroon kang pag-iisip na saktan ang iyong sarili? Huwag kang matakot na sagutin — nandito lang ako para sa iyo.',
            'is_crisis'       => true,
            'is_fallback'     => false,
            'created_at'      => Carbon::now()->subHours(3),
            'updated_at'      => Carbon::now()->subHours(3),
        ]);
        EmotionLog::create([
            'user_id'         => $alert3Student->id,
            'conversation_id' => $alert3Conv->id,
            'emotion'         => 'hopeless',
            'created_at'      => Carbon::now()->subHours(3),
            'updated_at'      => Carbon::now()->subHours(3),
        ]);
        $alert3 = CrisisAlert::create([
            'user_id'              => $alert3Student->id,
            'chat_message_id'      => $alert3Msg->id,
            'department'           => $alert3Student->department,
            'gender'               => $alert3Student->gender,
            'message'              => $alert3Msg->message,
            'severity'             => 'severe',
            'detected_keywords'    => ['huminto sa lahat', 'pagod na sa buhay'],
            'flag_reason'          => 'Student expressed desire to stop everything, possible passive suicidal ideation',
            'status'               => 'new',
            'is_classified'        => true,
            'admin_email_sent_at'  => null,
            'admin_email_notified' => false,
            'appointment_date'     => null,
            'appointment_time'     => null,
            'appointment_status'   => null,
            'created_at'           => Carbon::now()->subHours(3),
            'updated_at'           => Carbon::now()->subHours(3),
        ]);

        // Alert 4: Gian Carlo Dela Rosa — Moderate (dark thoughts from burnout)
        $alert4Student  = $students[32]; // Gian Carlo
        $alert4Conv     = Conversation::create([
            'user_id'      => $alert4Student->id,
            'email'        => $alert4Student->email,
            'title'        => 'Burnout at Dark Thoughts from Sleep Deprivation',
            'last_message' => 'I\'ve been feeling like nothing matters anymore. What\'s the point.',
            'is_saved'     => false,
            'is_archived'  => false,
            'created_at'   => Carbon::now()->subHours(6),
            'updated_at'   => Carbon::now()->subHours(2),
        ]);
        $alert4Msg = ChatMessage::create([
            'user_id'         => $alert4Student->id,
            'conversation_id' => $alert4Conv->id,
            'message'         => 'I\'ve been feeling like nothing matters anymore. What\'s the point.',
            'reply'           => 'What you\'re sharing sounds like deep emotional exhaustion that has reached a critical point. I want to make sure you\'re safe — when you say "what\'s the point," are you having any thoughts of harming yourself? Please know this is a safe space and I\'m here with you.',
            'is_crisis'       => true,
            'is_fallback'     => false,
            'created_at'      => Carbon::now()->subHours(2),
            'updated_at'      => Carbon::now()->subHours(2),
        ]);
        EmotionLog::create([
            'user_id'         => $alert4Student->id,
            'conversation_id' => $alert4Conv->id,
            'emotion'         => 'hopeless',
            'created_at'      => Carbon::now()->subHours(2),
            'updated_at'      => Carbon::now()->subHours(2),
        ]);
        $alert4 = CrisisAlert::create([
            'user_id'              => $alert4Student->id,
            'chat_message_id'      => $alert4Msg->id,
            'department'           => $alert4Student->department,
            'gender'               => $alert4Student->gender,
            'message'              => $alert4Msg->message,
            'severity'             => 'moderate',
            'detected_keywords'    => ['nothing matters', 'what\'s the point'],
            'flag_reason'          => 'Student expressing emotional numbness and anhedonia from prolonged sleep deprivation and burnout',
            'status'               => 'new',
            'is_classified'        => true,
            'admin_email_sent_at'  => null,
            'admin_email_notified' => false,
            'appointment_date'     => null,
            'appointment_time'     => null,
            'appointment_status'   => null,
            'created_at'           => Carbon::now()->subHours(2),
            'updated_at'           => Carbon::now()->subHours(2),
        ]);

        // Alert 5: Trisha Marie Guevarra — Mild (grief after patient loss)
        $alert5Student  = $students[16]; // Trisha Marie
        $alert5Conv     = Conversation::create([
            'user_id'      => $alert5Student->id,
            'email'        => $alert5Student->email,
            'title'        => 'Grief After Patient Death — Clinical Trauma',
            'last_message' => 'I keep thinking maybe if I had done something different the patient would have lived.',
            'is_saved'     => false,
            'is_archived'  => false,
            'created_at'   => Carbon::now()->subDays(2),
            'updated_at'   => Carbon::now()->subDays(1),
        ]);
        $alert5Msg = ChatMessage::create([
            'user_id'         => $alert5Student->id,
            'conversation_id' => $alert5Conv->id,
            'message'         => 'I keep thinking maybe if I had done something different the patient would have lived. I can\'t sleep and I keep crying.',
            'reply'           => 'What you are going through after witnessing a patient\'s death is a form of clinical grief and possibly moral distress. Please reach out to the Guidance Center or a mental health professional — this is not something you should carry alone. You did not cause this, and you deserve support.',
            'is_crisis'       => true,
            'is_fallback'     => false,
            'created_at'      => Carbon::now()->subDays(1),
            'updated_at'      => Carbon::now()->subDays(1),
        ]);
        EmotionLog::create([
            'user_id'         => $alert5Student->id,
            'conversation_id' => $alert5Conv->id,
            'emotion'         => 'sad',
            'created_at'      => Carbon::now()->subDays(1),
            'updated_at'      => Carbon::now()->subDays(1),
        ]);
        $alert5 = CrisisAlert::create([
            'user_id'              => $alert5Student->id,
            'chat_message_id'      => $alert5Msg->id,
            'department'           => $alert5Student->department,
            'gender'               => $alert5Student->gender,
            'message'              => $alert5Msg->message,
            'severity'             => 'low',
            'detected_keywords'    => ['patient died', 'can\'t sleep', 'crying'],
            'flag_reason'          => 'Student experiencing grief and moral distress following patient death during clinical duty',
            'status'               => 'reviewed',
            'is_classified'        => true,
            'admin_email_sent_at'  => Carbon::now()->subDays(1),
            'admin_email_notified' => true,
            'appointment_date'     => Carbon::now()->addDays(2)->toDateString(),
            'appointment_time'     => '2:00 PM',
            'appointment_status'   => 'scheduled',
            'created_at'           => Carbon::now()->subDays(1),
            'updated_at'           => Carbon::now()->subDays(1),
        ]);

        // --- Unclassified alerts (awaiting admin severity assignment) ---
        $unclassifiedSeeds = [
            [
                'user'     => $students[7], // Sophia Mae Garcia
                'title'    => 'Flagged: Overwhelmed After Duty',
                'message'  => 'Hindi ko na kaya. Parang gusto ko na lang mawala sandali.',
                'reply'    => 'Naririnig kita. Kapag ganito kabigat, importante na may kasama ka. Puwede mong lapitan ang Guidance Office — nandito din ako para makinig.',
                'keywords' => ['hindi ko na kaya', 'mawala'],
                'reason'   => 'Distress language detected; awaiting admin severity classification',
                'hours'    => 2,
            ],
            [
                'user'     => $students[11], // Alyssa Nicole Bautista
                'title'    => 'Flagged: Exam Panic Spiral',
                'message'  => 'My chest feels tight and I keep thinking something bad will happen if I fail this exam.',
                'reply'    => 'That sounds really frightening. Let\'s slow down together — you are safe right now. If the panic continues, please reach out to Guidance.',
                'keywords' => ['chest feels tight', 'something bad will happen'],
                'reason'   => 'Panic and catastrophic thinking markers flagged for review',
                'hours'    => 5,
            ],
            [
                'user'     => $students[14], // Nathaniel Castro
                'title'    => 'Flagged: Family Pressure Breakthrough',
                'message'  => 'Sobrang bigat na. Minsan iniisip ko kung may point pa ba magpatuloy.',
                'reply'    => 'Salamat sa pagiging honest. Ang nararamdaman mo ay sineseryoso ko. Mangyaring kausapin ang Guidance Counselor kung kaya — handa akong makinig.',
                'keywords' => ['sobrang bigat', 'may point pa ba'],
                'reason'   => 'Hopelessness / existential distress phrasing detected',
                'hours'    => 9,
            ],
            [
                'user'     => $students[21], // Camille Rose Domingo
                'title'    => 'Flagged: Loneliness and Dark Thoughts',
                'message'  => 'I feel so alone. Nobody would notice if I just disappeared.',
                'reply'    => 'I hear how lonely and heavy this feels. You matter, and people would notice. Please consider talking with the Guidance Office — I am here with you too.',
                'keywords' => ['so alone', 'disappeared'],
                'reason'   => 'Social withdrawal with disappearance ideation flagged',
                'hours'    => 14,
            ],
        ];

        $unclassifiedAlerts = [];
        foreach ($unclassifiedSeeds as $seed) {
            $uStudent = $seed['user'];
            $uConv = Conversation::create([
                'user_id'      => $uStudent->id,
                'email'        => $uStudent->email,
                'title'        => $seed['title'],
                'last_message' => $seed['message'],
                'is_saved'     => false,
                'is_archived'  => false,
                'created_at'   => Carbon::now()->subHours($seed['hours'] + 1),
                'updated_at'   => Carbon::now()->subHours($seed['hours']),
            ]);
            $uMsg = ChatMessage::create([
                'user_id'         => $uStudent->id,
                'conversation_id' => $uConv->id,
                'message'         => $seed['message'],
                'reply'           => $seed['reply'],
                'is_crisis'       => true,
                'is_fallback'     => false,
                'created_at'      => Carbon::now()->subHours($seed['hours']),
                'updated_at'      => Carbon::now()->subHours($seed['hours']),
            ]);
            EmotionLog::create([
                'user_id'         => $uStudent->id,
                'conversation_id' => $uConv->id,
                'emotion'         => 'hopeless',
                'created_at'      => Carbon::now()->subHours($seed['hours']),
                'updated_at'      => Carbon::now()->subHours($seed['hours']),
            ]);
            $unclassifiedAlerts[] = CrisisAlert::create([
                'user_id'              => $uStudent->id,
                'chat_message_id'      => $uMsg->id,
                'department'           => $uStudent->department,
                'gender'               => $uStudent->gender,
                'message'              => $seed['message'],
                'severity'             => null,
                'detected_keywords'    => $seed['keywords'],
                'flag_reason'          => $seed['reason'],
                'status'               => 'new',
                'is_classified'        => false,
                'admin_email_sent_at'  => null,
                'admin_email_notified' => false,
                'appointment_date'     => null,
                'appointment_time'     => null,
                'appointment_status'   => null,
                'created_at'           => Carbon::now()->subHours($seed['hours']),
                'updated_at'           => Carbon::now()->subHours($seed['hours']),
            ]);
        }

        // 5. Admin Notifications
        AdminNotification::crisisFlagged($alert1);
        AdminNotification::crisisFlagged($alert1b);
        AdminNotification::crisisFlagged($alert1c);
        AdminNotification::crisisFlagged($alert2);
        AdminNotification::crisisFlagged($alert3);
        AdminNotification::crisisFlagged($alert4);
        AdminNotification::crisisFlagged($alert5);
        foreach ($unclassifiedAlerts as $uAlert) {
            AdminNotification::crisisFlagged($uAlert);
        }

        // Simulate additional admin notification types
        AdminNotification::reportExported('last_30_days', ['emotions', 'sentiment', 'peak_hours']);
        AdminNotification::excelExported('last_7_days');
        AdminNotification::logExcelExported(142, ['department' => 'CCS']);
        AdminNotification::csvExported('last_30_days');

        // Urgent notification: Christian has 3 classified severe alerts (threshold is 2+)
        $christianSevereCount = CrisisAlert::where('user_id', $crisisStudent->id)
            ->where('severity', 'severe')
            ->where('is_classified', true)
            ->count();
        AdminNotification::urgentHelpNeeded($crisisStudent, $christianSevereCount);

        // 5b. Ensure EVERY student has at least one EN/Tagalog conversation (fills gaps)
        $fallbackTemplates = [
            [
                'title' => 'Pagod sa Deadlines at Quizzes',
                'messages' => [
                    [
                        'msg'     => 'Boss, sobrang dami ng deadlines this week. Parang hindi ko na alam kung saan magsisimula.',
                        'reply'   => 'Naiintindihan ko ang pressure mo. Subukan mong i-lista ang lahat, piliin ang pinaka-urgent, at gawin lang ang isa-isa. Hindi mo kailangang tapusin lahat ngayon.',
                        'emotion' => 'overwhelmed',
                    ],
                    [
                        'msg'     => 'Oo nga, baka magawa ko kung iisa-isahin ko. Salamat.',
                        'reply'   => 'Magandang plan iyan. Magpahinga ka rin kahit sandali — mas effective ang utak kapag may pahinga.',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
            [
                'title' => 'Exam Anxiety Before Midterms',
                'messages' => [
                    [
                        'msg'     => 'I keep blanking out whenever I review for midterms. My mind just goes empty.',
                        'reply'   => 'That blank-out feeling often comes from high anxiety, not from lack of studying. Try shorter review blocks with short breaks, and practice recalling without looking at notes.',
                        'emotion' => 'anxious',
                    ],
                    [
                        'msg'     => 'I will try the pomodoro method tonight.',
                        'reply'   => 'Great choice. Consistency beats cramming. I am here if the nerves come back.',
                        'emotion' => 'calm',
                    ],
                ],
            ],
            [
                'title' => 'Homesick at Away from Family',
                'messages' => [
                    [
                        'msg'     => 'Miss na miss ko na ang family ko. Parang ang hirap mag-aral dito mag-isa.',
                        'reply'   => 'Natural lang ang homesick, lalo na kapag bagong environment. Subukan mong mag-video call regularly, at humanap ng maliit na routine na nagpaparamdam ng “home.”',
                        'emotion' => 'sad',
                    ],
                    [
                        'msg'     => 'Oo, tatawag ako mamaya kay Mama.',
                        'reply'   => 'Maganda iyan. Connection with family can really recharge you. Ingat ka.',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
            [
                'title' => 'Group Project Conflict',
                'messages' => [
                    [
                        'msg'     => 'My groupmates are not doing their parts and I am carrying everything. I am so frustrated.',
                        'reply'   => 'Uneven group work is exhausting. Document what you have done, set a clear deadline with them, and if needed, loop in your instructor early — advocacy is part of teamwork.',
                        'emotion' => 'frustrated',
                    ],
                    [
                        'msg'     => 'Okay, I will message them tonight with a clear checklist.',
                        'reply'   => 'Clear expectations help a lot. Protect your energy while staying fair.',
                        'emotion' => 'calm',
                    ],
                ],
            ],
            [
                'title' => 'Kakulangan sa Tulog at Focus',
                'messages' => [
                    [
                        'msg'     => 'Tatlong araw na akong kulang sa tulog. Hindi na ako makafocus sa lecture.',
                        'reply'   => 'Ang sleep debt ay seryoso — apektado ang memory at mood. Subukan mong mag-set ng fixed bedtime tonight, kahit 6–7 hours muna, at iwasan ang screen 30 minutes before sleep.',
                        'emotion' => 'stressed',
                    ],
                    [
                        'msg'     => 'Sige, matutulog ako ng maaga tonight. Salamat.',
                        'reply'   => 'Good call. Rest is productivity, not laziness. Check in ulit anytime.',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
            [
                'title' => 'Internship Interview Nerves',
                'messages' => [
                    [
                        'msg'     => 'I have an internship interview tomorrow and my hands are shaking just thinking about it.',
                        'reply'   => 'Interview nerves mean you care. Prepare 3 stories about your projects, practice out loud once, then rest. You do not need to be perfect — just present and honest.',
                        'emotion' => 'anxious',
                    ],
                    [
                        'msg'     => 'Thanks. I will rehearse my introduction tonight.',
                        'reply'   => 'That is a solid plan. Breathe, pause when needed, and trust your preparation.',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
            [
                'title' => 'Pressure mula sa Magulang',
                'messages' => [
                    [
                        'msg'     => 'Palaging sinasabi ng parents ko na dapat top performer ako. Ang bigat sa dibdib ko.',
                        'reply'   => 'Ang parental expectations ay pwedeng maging heavy. Worth remembering: ang effort at growth mo ay valid kahit hindi perfect ang grades. Pwede mong i-share calmly kung ano ang kaya mong i-give.',
                        'emotion' => 'stressed',
                    ],
                    [
                        'msg'     => 'Mahirap i-explain sa kanila pero susubukan ko.',
                        'reply'   => 'Kahit maliit na honest conversation ay progress. Nandito ako if you need to practice what to say.',
                        'emotion' => 'anxious',
                    ],
                ],
            ],
            [
                'title' => 'Lonely in a Big Campus',
                'messages' => [
                    [
                        'msg'     => 'I eat lunch alone almost every day. It feels like everyone already has their circle.',
                        'reply'   => 'Feeling left out on campus is more common than it looks. Joining one org event, study group, or even a short chat after class can open a door — small steps count.',
                        'emotion' => 'lonely',
                    ],
                    [
                        'msg'     => 'Maybe I will try sitting near classmates and saying hi.',
                        'reply'   => 'That is brave and doable. Connection often starts with one simple hello.',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
            [
                'title' => 'Budget Stress sa Baon',
                'messages' => [
                    [
                        'msg'     => 'Kulang na ang baon ko this week. Stress na stress ako sa gastos.',
                        'reply'   => 'Financial stress is real for many students. Subukan mong mag-simple meal plan, iwasan ang impulse buys, at check kung may campus resources or student aid na pwedeng tumulong.',
                        'emotion' => 'stressed',
                    ],
                    [
                        'msg'     => 'Magli-list ako ng gastos ko starting today.',
                        'reply'   => 'Tracking helps a lot. One week of awareness can already reduce the overwhelm.',
                        'emotion' => 'calm',
                    ],
                ],
            ],
            [
                'title' => 'Presentation Fear in Class',
                'messages' => [
                    [
                        'msg'     => 'I have to present in front of the class and I feel like I will freeze.',
                        'reply'   => 'Stage fright is normal. Practice once standing up, look at friendly faces, and remember the class wants you to succeed more than they want to judge you.',
                        'emotion' => 'anxious',
                    ],
                    [
                        'msg'     => 'I will practice in front of the mirror tonight.',
                        'reply'   => 'Perfect. Familiarity lowers fear. You have got this.',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
            [
                'title' => 'Burnout After Clinical / Lab Duty',
                'messages' => [
                    [
                        'msg'     => 'Uwi ako from duty na parang walang laman. Pagod na pagod ang katawan at isip.',
                        'reply'   => 'Duty fatigue is real. Prioritize food, hydration, and short rest before more schoolwork. Recovery is part of being a good student and future professional.',
                        'emotion' => 'exhausted',
                    ],
                    [
                        'msg'     => 'Magpapahinga muna ako tonight, no cramming.',
                        'reply'   => 'Wise decision. Your body is asking for care — listen to it.',
                        'emotion' => 'calm',
                    ],
                ],
            ],
            [
                'title' => 'Comparing Myself to Classmates',
                'messages' => [
                    [
                        'msg'     => 'Everyone else seems smarter and more talented. I feel so behind.',
                        'reply'   => 'Comparison steals progress. You only see others’ highlights, not their late nights and doubts. Focus on your own next small improvement — that is how real growth happens.',
                        'emotion' => 'insecure',
                    ],
                    [
                        'msg'     => 'True. I will focus on my own pace for now.',
                        'reply'   => 'That mindset shift is powerful. Your path does not need to match anyone else’s.',
                        'emotion' => 'hopeful',
                    ],
                ],
            ],
        ];

        $studentsWithConversation = Conversation::query()
            ->whereIn('user_id', collect($students)->pluck('id'))
            ->pluck('user_id')
            ->unique();

        $filled = 0;
        foreach ($students as $sIdx => $stu) {
            if ($studentsWithConversation->contains($stu->id)) {
                continue;
            }

            $template = $fallbackTemplates[$sIdx % count($fallbackTemplates)];
            $lastMsg  = end($template['messages'])['msg'];
            $convTime = Carbon::now()->subDays(rand(1, 25))->subHours(rand(0, 12));

            $conversation = Conversation::create([
                'user_id'      => $stu->id,
                'email'        => $stu->email,
                'title'        => $template['title'],
                'last_message' => $lastMsg,
                'is_saved'     => ($sIdx % 4 === 0),
                'is_archived'  => false,
                'created_at'   => $convTime,
                'updated_at'   => $convTime->copy()->addHours(rand(1, 6)),
            ]);

            foreach ($template['messages'] as $mIdx => $m) {
                $msgTime = $convTime->copy()->addMinutes($mIdx * 8);
                ChatMessage::create([
                    'user_id'         => $stu->id,
                    'conversation_id' => $conversation->id,
                    'message'         => $m['msg'],
                    'reply'           => $m['reply'],
                    'is_crisis'       => false,
                    'is_fallback'     => false,
                    'created_at'      => $msgTime,
                    'updated_at'      => $msgTime,
                ]);
            }

            EmotionLog::create([
                'user_id'         => $stu->id,
                'conversation_id' => $conversation->id,
                'emotion'         => end($template['messages'])['emotion'],
                'created_at'      => $convTime->copy()->addMinutes(20),
                'updated_at'      => $convTime->copy()->addMinutes(20),
            ]);

            $filled++;
        }

        $this->command->info("Filled conversations for {$filled} students who previously had none.");

        // Give each student a second conversation for richer student + admin dashboards
        $secondPass = 0;
        foreach ($students as $sIdx => $stu) {
            $existingCount = Conversation::where('user_id', $stu->id)->count();
            if ($existingCount >= 2) {
                continue;
            }

            $template = $fallbackTemplates[($sIdx + 5) % count($fallbackTemplates)];
            $lastMsg  = end($template['messages'])['msg'];
            $convTime = Carbon::now()->subDays(rand(1, 14))->subHours(rand(0, 8));

            $conversation = Conversation::create([
                'user_id'      => $stu->id,
                'email'        => $stu->email,
                'title'        => $template['title'] . ' (Follow-up)',
                'last_message' => $lastMsg,
                'is_saved'     => false,
                'is_archived'  => false,
                'created_at'   => $convTime,
                'updated_at'   => $convTime->copy()->addHours(2),
            ]);

            foreach ($template['messages'] as $mIdx => $m) {
                $msgTime = $convTime->copy()->addMinutes($mIdx * 6);
                ChatMessage::create([
                    'user_id'         => $stu->id,
                    'conversation_id' => $conversation->id,
                    'message'         => $m['msg'],
                    'reply'           => $m['reply'],
                    'is_crisis'       => false,
                    'is_fallback'     => false,
                    'created_at'      => $msgTime,
                    'updated_at'      => $msgTime,
                ]);
            }

            EmotionLog::create([
                'user_id'         => $stu->id,
                'conversation_id' => $conversation->id,
                'emotion'         => end($template['messages'])['emotion'],
                'created_at'      => $convTime->copy()->addMinutes(15),
                'updated_at'      => $convTime->copy()->addMinutes(15),
            ]);

            $secondPass++;
        }

        $this->command->info("Added a second conversation for {$secondPass} students.");

        // 6. Mood Entries Spread Over the Last 30 Days
        $moodOptions = ['happy', 'neutral', 'stressed', 'anxious', 'sad', 'calm', 'overwhelmed'];
        foreach ($students as $stu) {
            for ($d = 30; $d >= 0; $d--) {
                if (rand(0, 10) > 4) {
                    $selectedMood = $moodOptions[array_rand($moodOptions)];
                    $moodTime     = Carbon::now()->subDays($d)->subHours(rand(1, 12));

                    MoodEntry::create([
                        'user_id'    => $stu->id,
                        'mood'       => $selectedMood,
                        'created_at' => $moodTime,
                        'updated_at' => $moodTime,
                    ]);
                }
            }
        }

        // 7. Session logs so admin student insights show non-zero session_count
        foreach ($students as $stu) {
            $sessionCount = rand(3, 10);
            for ($s = 0; $s < $sessionCount; $s++) {
                $start = Carbon::now()->subDays(rand(0, 28))->setTime(rand(7, 21), rand(0, 59));
                $end   = (clone $start)->addMinutes(rand(8, 55));

                SessionLog::create([
                    'user_id'       => $stu->id,
                    'session_start' => $start,
                    'session_end'   => $end,
                    'created_at'    => $start,
                    'updated_at'    => $end,
                ]);
            }
        }

        $this->command->info(
            'Local test data successfully seeded! '
            . count($students) . ' students, '
            . Conversation::count() . ' conversations, '
            . ChatMessage::count() . ' messages, '
            . EmotionLog::count() . ' emotion logs, '
            . MoodEntry::count() . ' mood entries, '
            . SessionLog::count() . ' session logs, '
            . CrisisAlert::count() . ' crisis alerts, '
            . AdminNotification::count() . ' admin notifications.'
        );
    }
}
