<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Faq;

class FaqSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faqs = [
            [
                "id" => 11,
                "user_id" => 2,
                "question" => "If for what ever reason I want to stop using SonoLinq, how would I close my account?",
                "answer" => "With SonoLinq we pride ourselves on taking care of our clients from start to finish, and while \nwe don't want to see anyone go, we respect your decision to do so. You can do two things in \nthis case. First, you can simply stop using the platform, that way if you decide to come back at \nany point in the future, you simply log back in and you're already set. Second, if you would like \nto actually close your account, simply reach out to customer support letting us know you wish \nto do so and we will take care of it immediately. No hidden fees or processes involved.",
                "status" => 1
            ],
            [
                "id" => 10,
                "user_id" => 2,
                "question" => "Can these jobs turn into permanent positions through SonoLinq?",
                "answer" => "Yes! The SonoLinq platform is designed for pure convenience all the way around. Whether \nyou want to cover a day or so here and there or if you find a place that requires more recurring \nlong term needs, SonoLinq is here to bridge that gap and make the process very simple for both \nthe sonographer and doctor/facility.",
                "status" => 1
            ],
            [
                "id" => 9,
                "user_id" => 2,
                "question" => "Who do I contact if I have trouble with my booking?",
                "answer" => "SonoLinq brings the two parties together (sonographer and doctor facility) to make \ntroubleshooting issues, knowing you're talking to the right person in most cases, simple. If you \nhave trouble with your booking, in most cases, this can be resolved by communicating between one another (sonographer and doctor/facility). In the more rare cases you have trouble beyond \nwhat one of you (sonographer or doctor/facility) can address, SonoLinq customer support is just \na few clicks, a quick phone call, or chat conversation away, which ever you prefer.",
                "status" => 1
            ],
            [
                "id" => 8,
                "user_id" => 2,
                "question" => "Is the SonoLinq platform a free service?",
                "answer" => "Yes! There are no monthly fees or dues.",
                "status" => 1
            ],
            [
                "id" => 7,
                "user_id" => 2,
                "question" => "How is my pay as a sonographer calculated when I sign up through SonoLinq?",
                "answer" => "Sonographer pay is based on a number of factors: experience, registries held, number of \nscans one can perform, rates of pay a given market can support as a contractor vs. W2, etc. \nEach data set has a value assigned to it. The more you're able to do, the higher your value. It's \nthat simple. Owned and operated by sonographers with 20+ years experience in the industry, \nSonoLinq priortizes making sure the sonographers are very well compensated, while striking the \nbalance of also making sure the doctors/facilities get a great value for their investment. All \nparties win in this.",
                "status" => 1
            ],
            [
                "id" => 6,
                "user_id" => 2,
                "question" => "How is SonoLinq different from Indeed or other big online job sites?",
                "answer" => "SonoLinq is nothing like the big job sites, it's better. It's better in every way. Using the big \nonline is cumbersome, laborious and expensive. They charge you to use their platform, then \nyou do all the work. Recruiting, reviewing countless resumes (most of which are unqualified, \nunder qualified, don't match your needs). Then sorting through and interviewing countless \ncandidates, is all left up to you to do. As if you don't have enough to do already. Even worse, if \nnone of the candidates work out, you get to start the frustrating and fruitless process all over \nagain. You already know what they say about doing the same thing over and over again but \nexpecting a different result. Big online job sites ask you to do just that. Not SonoLinq though. \nSonoLinq is free, does the recruiting and vetting for you through the use of cutting edge \nmachine learning software, all based upon specific parameters you've set, coupled with live \nprofesional support, which will deliver an expert sonographer to you which matches your \nspecific needs. Just give SonoLinq a try and experience the difference",
                "status" => 1
            ],
            [
                "id" => 5,
                "user_id" => 2,
                "question" => "As the doctor or facility, what if I have to cancel last minute?",
                "answer" => "Don't worry there's only a 10% service fee for testing days canceled within 24 hours of the \nappointment. Otherwise, if you cancel with 25+ hour notice, it's completely free!",
                "status" => 1
            ],
            [
                "id" => 4,
                "user_id" => 2,
                "question" => "Is there a membership fee to use SonoLinq?",
                "answer" => "No! It's completely free, no catches.",
                "status" => 1
            ],
            [
                "id" => 3,
                "user_id" => 2,
                "question" => "If the sonographer doesn't show up do I get a refund?",
                "answer" => "In short, yes. You are never charged up front. Your funds are held in escrow and simply \nreturned if services aren't rendered due to fault of the sonographer, no questions asked.",
                "status" => 1
            ],
            [
                "id" => 2,
                "user_id" => 2,
                "question" => "SonoLinq is asking for my debit/credit card or banking information before I can place a  request for a booking. Do I have to pay before services are rendered?",
                "answer" => "No, you do not have to pay prior to services rendered. Payment is simply held in escrow and \nonly released to the sonographer after service has been rendered.",
                "status" => 1
            ],
            [
                "id" => 1,
                "user_id" => 2,
                "question" => "How long do I have to wait to get a response or acceptance for my job request?",
                "answer" => "Response times will always vary based on a number of factors such as individual sonographer \navailability, how many sonographers meet your requirements, day or time of day you placed \nrequest, how far in advance you've placed your job request for, etc. The more you use the \nsystem the better you will be able to determine lead times on any given request. Furthermore, \nadjusting your requirements could increase your job acceptance times.",
                "status" => 1
            ]
        ];

        foreach ($faqs as $faq) {
            Faq::create($faq);
        }
    }
}