<?php
namespace Database\Seeders;
use App\Models\{Automation, Course, CourseSession};
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $j = json_decode(file_get_contents(__DIR__ . '/data/arcgis.json'), true);
        $contact = ['whatsapp' => '213662895298', 'ccp_number' => '0000000000 CLE 00', 'ccp_name' => 'VOTRE NOM', 'rip' => '00799999000000000000', 'group' => ''];

        $arc = Course::updateOrCreate(['slug' => 'arcgis'], ['title' => $j['title'], 'content' => $j['content'], 'seo' => $j['seo'], 'addons' => $j['addons'], 'contact' => $contact, 'weeks' => 6]);
        $free = Course::updateOrCreate(['slug' => 'atelier-gratuit'], [
            'title' => ['ar' => 'ورشة ArcGIS مجانية', 'fr' => 'Atelier ArcGIS gratuit', 'en' => 'Free ArcGIS workshop'],
            'content' => $j['workshop'], 'contact' => $contact, 'weeks' => 1,
        ]);
        if (!$arc->sessions()->exists())
            CourseSession::create(['course_id' => $arc->id, 'starts_at' => '2026-11-14 18:00:00', 'mode' => 'online', 'meeting_url' => 'https://meet.example.com/arcgis', 'seats' => 20, 'price' => 60000, 'early_price' => 45000, 'early_seats' => 10]);
        if (!$free->sessions()->exists())
            CourseSession::create(['course_id' => $free->id, 'starts_at' => '2026-11-05 18:00:00', 'mode' => 'online', 'meeting_url' => 'https://meet.example.com/atelier', 'seats' => 100, 'price' => 0]);

        if (Automation::exists()) return;
        $A = fn ($name, $anchor, $off, $aud, $ch, $subject, $body, $extra = []) => Automation::create(['name' => $name, 'anchor' => $anchor, 'offset_minutes' => $off, 'audience' => $aud, 'channels' => $ch, 'subject' => $subject, 'body' => $body] + $extra);

        $A('Welcome (free registration)', 'registration', 0, 'free', ['mail', 'whatsapp'],
            ['ar' => 'تسجيلك في {course}', 'fr' => 'Votre inscription : {course}', 'en' => 'Your registration: {course}'],
            ['ar' => "مرحبًا {name} 👋\nتم تسجيلك في «{course}».\nالموعد: {date} على الساعة {time}\nالمكان/الرابط: {location} {link}\nتذكرتك: {ticket}",
             'fr' => "Bonjour {name} 👋\nVotre inscription à « {course} » est confirmée.\nDate : {date} à {time}\nLieu/lien : {location} {link}\nVotre ticket : {ticket}",
             'en' => "Hi {name} 👋\nYou're registered for “{course}”.\nWhen: {date} at {time}\nWhere/link: {location} {link}\nYour ticket: {ticket}"]);
        $A('Payment confirmed', 'paid', 0, 'paid', ['mail', 'whatsapp'],
            ['ar' => 'تم تأكيد الدفع ✅', 'fr' => 'Paiement confirmé ✅', 'en' => 'Payment confirmed ✅'],
            ['ar' => "شكرًا {name}! تم تأكيد دفعك لـ «{course}».\nأول حصة: {date} على الساعة {time}\nتذكرتك والتقويم: {ticket}",
             'fr' => "Merci {name} ! Paiement confirmé pour « {course} ».\nPremière séance : {date} à {time}\nTicket et calendrier : {ticket}",
             'en' => "Thanks {name}! Payment confirmed for “{course}”.\nFirst session: {date} at {time}\nTicket & calendar: {ticket}"]);
        $A('Reminder 24h before each class', 'meeting', -1440, 'all', ['mail', 'whatsapp'],
            ['ar' => 'تذكير: حصة غدًا', 'fr' => 'Rappel : séance demain', 'en' => 'Reminder: class tomorrow'],
            ['ar' => "تذكير {name}: حصة «{course}» غدًا {date} على الساعة {time}.\n{location} {link}",
             'fr' => "Rappel {name} : séance « {course} » demain, {date} à {time}.\n{location} {link}",
             'en' => "Reminder {name}: “{course}” class tomorrow, {date} at {time}.\n{location} {link}"]);
        $A('Reminder 1h before each class', 'meeting', -60, 'all', ['whatsapp', 'sms'],
            ['ar' => 'الحصة بعد ساعة', 'fr' => 'Séance dans 1 h', 'en' => 'Class in 1 hour'],
            ['ar' => 'الحصة تبدأ بعد ساعة ({time}). {location} {link}', 'fr' => 'La séance commence dans 1 h ({time}). {location} {link}', 'en' => 'Class starts in 1 hour ({time}). {location} {link}']);
        $A('Promo: free workshop attendees -> paid course', 'registration', 2880, 'free', ['whatsapp', 'mail'],
            ['ar' => 'التكوين الكامل يبدأ قريبًا', 'fr' => 'La formation complète démarre bientôt', 'en' => 'The full training starts soon'],
            ['ar' => "مرحبًا {name}، بعد «{course}» يمكنك متابعة التكوين الكامل (6 أسابيع + مشروع نهائي).\nسعر المسجلين الأوائل متاح لمدة محدودة: {promo}",
             'fr' => "Bonjour {name}, après « {course} », découvrez la formation complète (6 semaines + projet final).\nTarif early bird pour une durée limitée : {promo}",
             'en' => "Hi {name}, after “{course}”, check out the full training (6 weeks + final project).\nEarly-bird price for a limited time: {promo}"],
            ['course_id' => $free->id, 'is_promo' => true, 'promote_course_id' => $arc->id]);
    }
}
