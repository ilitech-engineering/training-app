<?php
namespace App\Http\Controllers;
use App\Models\Course;
use Illuminate\Http\Request;

class CourseController extends Controller
{
    public static function locale(Request $r): string
    {
        $l = $r->query('lang', session('lang', 'ar'));
        $l = in_array($l, config('training.locales')) ? $l : 'ar';
        session(['lang' => $l]); app()->setLocale($l);
        return $l;
    }

    public function index(Request $r)
    {
        $loc = self::locale($r);
        $courses = Course::where('active', true)->with(['sessions' => fn ($q) => $q->where('status', 'open')->where('starts_at', '>', now())->orderBy('starts_at')])->get();
        return view('index', compact('courses', 'loc'));
    }

    public function show(Request $r, Course $course)
    {
        abort_unless($course->active, 404);
        $loc = self::locale($r);
        $sessions = $course->sessions()->where('status', 'open')->where('starts_at', '>', now())->orderBy('starts_at')->get();
        return view('course', compact('course', 'sessions', 'loc'));
    }

    public function sitemap()
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';
        foreach (Course::where('active', true)->get() as $c) {
            $xml .= '<url><loc>' . route('course', $c->slug) . '</loc><lastmod>' . $c->updated_at->toDateString() . '</lastmod>';
            foreach (config('training.locales') as $l) $xml .= '<xhtml:link rel="alternate" hreflang="' . $l . '" href="' . route('course', $c->slug) . '?lang=' . $l . '"/>';
            $xml .= '</url>';
        }
        return response($xml . '</urlset>', 200, ['Content-Type' => 'application/xml']);
    }
}
