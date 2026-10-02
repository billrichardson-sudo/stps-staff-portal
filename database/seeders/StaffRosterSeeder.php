<?php

namespace Database\Seeders;

use App\Models\Staff;
use Illuminate\Database\Seeder;

/**
 * One-time import of the real STPS staff roster (125 staff, Oct 2026).
 * Employee IDs are assigned sequentially (STP001-STP125) in the order staff
 * appear on the source sheet. Initial password for every account is
 * "Staff@1938"; must_change_password forces a reset on first login.
 *
 * Organisational mapping confirmed by HR:
 *  - Headmaster: row 95 (Rev. Nihal Fernando)
 *  - HR: row 110 (Miss T H Yasuri Yasangi De Silva)
 *  - Sectional Head, Primary: row 1 (Mrs. Nishanthi Logaraj)
 *  - Sectional Head, Middle: row 45 (Ms. Yvonne Charles)
 *  - Sectional Head, Upper: row 58 (Mr. Nilushan Silva)
 *  - Supervisor, Support/Maintenance: row 103 (Mr. Liyanaarachchi) -
 *    Admin category himself, but acts as first approver for Support staff.
 *  - HR: row 106 (Mr. Bill Richardson) also granted HR access alongside
 *    row 110, as the system administrator.
 *  - Cross-Section staff (rows 76-94) were individually assigned to one of
 *    the three sectional heads above; row 91 had no assignment given, so it
 *    defaults to the Middle head (Ms. Yvonne Charles) - flagged for HR to
 *    confirm/change via Staff management.
 */
class StaffRosterSeeder extends Seeder
{
    public function run(): void
    {
        $roster = [
            // Primary
            1 => ['name' => 'Mrs. Nishanthi Logaraj', 'section' => 'Primary'],
            2 => ['name' => 'Mrs. Methma Fernando', 'section' => 'Primary'],
            3 => ['name' => 'Mrs. Faruza Irfan', 'section' => 'Primary'],
            4 => ['name' => 'Mrs. Anusha Perera', 'section' => 'Primary'],
            5 => ['name' => 'Mrs. Kanchana Mohan', 'section' => 'Primary'],
            6 => ['name' => 'Mrs. Sadeeka Dassanayake', 'section' => 'Primary'],
            7 => ['name' => 'Mrs. Nishanthi Peiris', 'section' => 'Primary'],
            8 => ['name' => 'Mrs. Hirani Thilakarathna', 'section' => 'Primary'],
            9 => ['name' => 'Mrs. Roshila Fernando', 'section' => 'Primary'],
            10 => ['name' => 'Mrs. Rebecca Sureshkumar', 'section' => 'Primary'],
            11 => ['name' => 'Mrs. Priyashi De Silva', 'section' => 'Primary'],
            12 => ['name' => 'Mrs. Sithumi Silva', 'section' => 'Primary'],
            13 => ['name' => 'Mrs. Balari Gabadamudalige', 'section' => 'Primary'],
            14 => ['name' => 'Mrs. Saranja Sivaraj', 'section' => 'Primary'],
            15 => ['name' => 'Mrs. Lily Diana', 'section' => 'Primary'],
            16 => ['name' => 'Mrs. Roshika Caldera', 'section' => 'Primary'],
            17 => ['name' => 'Mrs. Mihiri Fernando', 'section' => 'Primary'],
            18 => ['name' => 'Mrs. Navodya Perera', 'section' => 'Primary'],
            19 => ['name' => 'Mrs. Malithi Munasinghe', 'section' => 'Primary'],
            20 => ['name' => 'Ms. Sharala Ferdinandez', 'section' => 'Primary'],
            21 => ['name' => 'Miss Thomas Dorris', 'section' => 'Primary'],
            22 => ['name' => 'Mrs. Sahithya Gajamugan', 'section' => 'Primary'],
            23 => ['name' => 'Mrs. Jithmi Sithasha', 'section' => 'Primary'],
            24 => ['name' => 'Mrs. Jananie Ganeshan', 'section' => 'Primary'],
            25 => ['name' => 'Mrs. Sharmila Paranitharan', 'section' => 'Primary'],
            26 => ['name' => 'Miss Kugathas Saruja', 'section' => 'Primary'],
            27 => ['name' => 'Miss Karvannan Sangeetha', 'section' => 'Primary'],
            28 => ['name' => 'Mrs. Ransirini Chathurika', 'section' => 'Primary'],
            29 => ['name' => 'Mrs. Shanika Sooriyagamage', 'section' => 'Primary'],
            30 => ['name' => 'Mrs. Priyawadani Maheshwaran', 'section' => 'Primary'],
            31 => ['name' => 'Ms. Navodi Perera', 'section' => 'Primary'],
            32 => ['name' => 'Mrs. Thilina Nirmani', 'section' => 'Primary'],
            33 => ['name' => 'Mrs. Sivaraj Rosaaniya', 'section' => 'Primary'],
            34 => ['name' => 'Mrs. Rajanthini Sethukavalr', 'section' => 'Primary'],
            35 => ['name' => 'Mrs. Oshani Samarathunga', 'section' => 'Primary'],
            36 => ['name' => 'Mrs. Manjula Mohimani', 'section' => 'Primary'],
            37 => ['name' => 'Mrs. Jothi Mohinani', 'section' => 'Primary'],
            38 => ['name' => 'Mrs. Shalini Gunasekara', 'section' => 'Primary'],
            39 => ['name' => 'Ms. Geema Ayodhya', 'section' => 'Primary'],
            40 => ['name' => 'Ms. Jithara Hewagamage', 'section' => 'Primary'],
            41 => ['name' => 'Mrs. Diedri', 'section' => 'Primary'],
            42 => ['name' => 'Mr. V. Piruba', 'section' => 'Primary'],
            43 => ['name' => 'Mr. Jeromaiah', 'section' => 'Primary'],
            44 => ['name' => 'Mrs. Nadeeshika', 'section' => 'Primary'],

            // Middle
            45 => ['name' => 'Ms. Yvonne Charles', 'section' => 'Middle'],
            46 => ['name' => 'Mrs. Ann Arnold', 'section' => 'Middle'],
            47 => ['name' => 'Mr. Kumara De Silva', 'section' => 'Middle'],
            48 => ['name' => 'Ms. Chathuri Wimalasiri', 'section' => 'Middle'],
            49 => ['name' => 'Mr. Buddhika Madusanka', 'section' => 'Middle'],
            50 => ['name' => 'Mr. Jithmal Wijesinghe', 'section' => 'Middle'],
            51 => ['name' => 'Mr. Shaluka Dassanayaka', 'section' => 'Middle'],
            52 => ['name' => 'Ms. Thowfeek Epshiba', 'section' => 'Middle'],
            53 => ['name' => 'Miss Sheron Dissanayaka', 'section' => 'Middle'],
            54 => ['name' => 'Mrs. Tani Nithaniya', 'section' => 'Middle'],
            55 => ['name' => 'Mr. Supun Malalage', 'section' => 'Middle'],
            56 => ['name' => 'Mr. Shantha Warnasiri', 'section' => 'Middle'],
            57 => ['name' => 'Mr. M. Thayaparan', 'section' => 'Middle'],

            // Upper
            58 => ['name' => 'Mr. Nilushan Silva', 'section' => 'Upper'],
            59 => ['name' => 'Mr. Upul Navaratne', 'section' => 'Upper'],
            60 => ['name' => 'Mrs. Nimali Gamage', 'section' => 'Upper'],
            61 => ['name' => 'Mrs. S. Manmathakanthan', 'section' => 'Upper'],
            62 => ['name' => 'Mr. R. Yasodaran', 'section' => 'Upper'],
            63 => ['name' => 'Mr. M.I. Kumara', 'section' => 'Upper'],
            64 => ['name' => 'Mr. S. Ravichandran', 'section' => 'Upper'],
            65 => ['name' => 'Mrs. A. Iresha Perera', 'section' => 'Upper'],
            66 => ['name' => 'Mr. T. Ramesh', 'section' => 'Upper'],
            67 => ['name' => 'Mrs. Chamila Fernando', 'section' => 'Upper'],
            68 => ['name' => 'Mrs. Sarah Hashwar', 'section' => 'Upper'],
            69 => ['name' => 'Mr. Chathuranga Kariyawasam', 'section' => 'Upper'],
            70 => ['name' => 'Mrs. Mayuri Jayasiri', 'section' => 'Upper'],
            71 => ['name' => 'Mrs. V.V Lalvani', 'section' => 'Upper'],
            72 => ['name' => 'Mr. K. Shanmuganandan', 'section' => 'Upper'],
            73 => ['name' => 'Mr. M. Theannilawu', 'section' => 'Upper'],
            74 => ['name' => 'Mrs. R.K. Halaldeen', 'section' => 'Upper'],
            75 => ['name' => 'Rev. Cyril', 'section' => 'Upper'],

            // Cross-Section
            76 => ['name' => 'Mrs. Raffek', 'section' => 'Cross-Section'],
            77 => ['name' => 'Mrs. Piyamini Nirodhawardana', 'section' => 'Cross-Section'],
            78 => ['name' => 'I M N Apeksha Illangakoon', 'section' => 'Cross-Section'],
            79 => ['name' => 'Mr. Harin Amirthanathan', 'section' => 'Cross-Section'],
            80 => ['name' => 'Mr. Manjula Illangakoon', 'section' => 'Cross-Section'],
            81 => ['name' => 'Ms. Prashani Alwis', 'section' => 'Cross-Section'],
            82 => ['name' => 'Mr. Heman Denawaka', 'section' => 'Cross-Section'],
            83 => ['name' => 'Mrs. Suhara Nawas', 'section' => 'Cross-Section'],
            84 => ['name' => 'Miss S. Sarvalogeswary', 'section' => 'Cross-Section'],
            85 => ['name' => 'Mrs. M.N. Hariharan', 'section' => 'Cross-Section'],
            86 => ['name' => 'Mrs. Lihini Samarakoon', 'section' => 'Cross-Section'],
            87 => ['name' => 'Mrs. Ashani Godakumbura', 'section' => 'Cross-Section'],
            88 => ['name' => 'Mrs. Ashini Wickramasekara', 'section' => 'Cross-Section'],
            89 => ['name' => 'Mr. Sameera Fonseka', 'section' => 'Cross-Section'],
            90 => ['name' => 'Mr. Saranga Wickramage', 'section' => 'Cross-Section'],
            91 => ['name' => 'Mr. Gayan Binduhewa', 'section' => 'Cross-Section'],
            92 => ['name' => 'Mrs. Madhusha Fernando', 'section' => 'Cross-Section'],
            93 => ['name' => 'Mrs. Rathiga Anbalagan', 'section' => 'Cross-Section'],
            94 => ['name' => 'Mrs. N. Dissanayake', 'section' => 'Cross-Section'],

            // Admin
            95 => ['name' => 'Rev. Nihal Fernando', 'section' => 'Admin'],
            96 => ['name' => 'Mrs. Hiranya Fernando', 'section' => 'Admin'],
            97 => ['name' => 'Mrs. Nipuni Nugaliyadda', 'section' => 'Admin'],
            98 => ['name' => 'Mr. Milndu Liyanaarchchi', 'section' => 'Admin'],
            99 => ['name' => 'Mrs. Ashwini Gunasekara', 'section' => 'Admin'],
            100 => ['name' => 'Mrs. Diana Jansen', 'section' => 'Admin'],
            101 => ['name' => 'Mrs. Chrishanthi Perera', 'section' => 'Admin'],
            102 => ['name' => 'Mr. Shaikh Mazeen', 'section' => 'Admin'],
            103 => ['name' => 'Mr. Liyanaarachchi', 'section' => 'Admin'],
            104 => ['name' => 'Mr. Madusanka', 'section' => 'Admin'],
            105 => ['name' => 'Mr. Sheshan Fernando', 'section' => 'Admin'],
            106 => ['name' => 'Mr. Bill Richardson', 'section' => 'Admin'],
            107 => ['name' => 'Miss Pamodha Subhasinghe', 'section' => 'Admin'],
            108 => ['name' => 'Mr. Ravindra De Silva', 'section' => 'Admin'],
            109 => ['name' => 'Mrs. Rangama Nilanthi', 'section' => 'Admin'],
            110 => ['name' => 'Miss T H Yasuri Yasangi De Silva', 'section' => 'Admin'],
            111 => ['name' => 'Mr. Damith Udayanga', 'section' => 'Admin'],
            112 => ['name' => 'Mr. Siraj Dahalan', 'section' => 'Admin'],

            // Support/Maintenance
            113 => ['name' => 'Mr. Amal Sirikumara', 'section' => 'Support/Maintenance'],
            114 => ['name' => 'Mr. Arul Nesamani', 'section' => 'Support/Maintenance'],
            115 => ['name' => 'Mr. Nishantha Podinilame', 'section' => 'Support/Maintenance'],
            116 => ['name' => 'Mr. Prasad Suren', 'section' => 'Support/Maintenance'],
            117 => ['name' => 'Mr. Subramaniyam', 'section' => 'Support/Maintenance'],
            118 => ['name' => 'Mr. Suranga Kumara', 'section' => 'Support/Maintenance'],
            119 => ['name' => 'Mr. S. Pushpakumara', 'section' => 'Support/Maintenance'],
            120 => ['name' => 'Mr. T.M.D. Shanaka', 'section' => 'Support/Maintenance'],
            121 => ['name' => 'Mrs. Sudarshanie', 'section' => 'Support/Maintenance'],
            122 => ['name' => 'Mr. Chaminda Irosh', 'section' => 'Support/Maintenance'],
            123 => ['name' => 'Mr. Camillus', 'section' => 'Support/Maintenance'],
            124 => ['name' => 'Mr. Sarath Kumarasiri', 'section' => 'Support/Maintenance'],
            125 => ['name' => 'Mr. Champika', 'section' => 'Support/Maintenance'],
        ];

        $sectionMap = [
            'Primary' => ['category' => 'Tutorial', 'department' => 'Primary'],
            'Middle' => ['category' => 'Tutorial', 'department' => 'Middle'],
            'Upper' => ['category' => 'Tutorial', 'department' => 'Upper'],
            'Cross-Section' => ['category' => 'Tutorial', 'department' => 'Cross-Section'],
            'Admin' => ['category' => 'Admin', 'department' => 'Admin'],
            'Support/Maintenance' => ['category' => 'Support', 'department' => 'Support/Maintenance'],
        ];

        $ids = [];

        foreach ($roster as $row => $person) {
            $map = $sectionMap[$person['section']];
            $empId = sprintf('STP%03d', $row);
            $staff = Staff::where('emp_id', $empId)->first();

            if ($staff) {
                // Re-running this seeder (e.g. to fix a name/section typo)
                // must never touch password/must_change_password - staff may
                // already have signed in and changed their password.
                $staff->update([
                    'name' => $person['name'],
                    'category' => $map['category'],
                    'department' => $map['department'],
                ]);
            } else {
                $staff = Staff::create([
                    'emp_id' => $empId,
                    'name' => $person['name'],
                    'category' => $map['category'],
                    'department' => $map['department'],
                    'role' => 'staff',
                    'status' => 'Active',
                    'password' => 'Staff@1938',
                    'must_change_password' => true,
                ]);
            }

            $ids[$row] = $staff->id;
        }

        // Role overrides for named approvers.
        Staff::whereKey($ids[95])->update(['role' => 'headmaster']);
        Staff::whereKey($ids[110])->update(['role' => 'hr']);
        Staff::whereKey($ids[1])->update(['role' => 'sectional_head']);
        Staff::whereKey($ids[45])->update(['role' => 'sectional_head']);
        Staff::whereKey($ids[58])->update(['role' => 'sectional_head']);
        Staff::whereKey($ids[103])->update(['role' => 'supervisor']);
        Staff::whereKey($ids[106])->update(['role' => 'hr']); // Bill Richardson - system administrator

        // First-approver routing: Primary/Middle/Upper teachers -> their head.
        foreach (range(2, 44) as $row) {
            Staff::whereKey($ids[$row])->update(['first_approver_id' => $ids[1]]);
        }
        foreach (range(46, 57) as $row) {
            Staff::whereKey($ids[$row])->update(['first_approver_id' => $ids[45]]);
        }
        foreach (range(59, 75) as $row) {
            Staff::whereKey($ids[$row])->update(['first_approver_id' => $ids[58]]);
        }

        // Cross-Section staff, individually assigned to one of the three
        // sectional heads. Row 91 (Mr. Gayan Binduhewa) had no assignment
        // given by HR and defaults to the Middle head - flagged for review.
        $crossSectionHeads = [
            76 => 45, 77 => 45, 78 => 58, 79 => 45, 80 => 1, 81 => 45, 82 => 45,
            83 => 45, 84 => 58, 85 => 45, 86 => 58, 87 => 58, 88 => 58, 89 => 58,
            90 => 58, 91 => 45, 92 => 58, 93 => 45, 94 => 58,
        ];
        foreach ($crossSectionHeads as $row => $headRow) {
            Staff::whereKey($ids[$row])->update(['first_approver_id' => $ids[$headRow]]);
        }

        // Support/Maintenance staff -> the supervisor (row 103).
        foreach (range(113, 125) as $row) {
            Staff::whereKey($ids[$row])->update(['first_approver_id' => $ids[103]]);
        }
    }
}
