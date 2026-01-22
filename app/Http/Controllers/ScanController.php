<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Scan;
use App\Models\ScanResult;
use App\Services\AIService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Http;

class ScanController extends Controller
{
    //استقبال الرابط وبدء الفحص
   public function startScan(Request $request, AIService $ai)
    {
        // 🔍 Validate input
        $request->validate([
            'url' => 'required|url'
        ]);

        // 📝 Create main scan record
        $scan = Scan::create([
            'user_id'   => $request->user()->id,
            'url'       => $request->url,
            'status'    => 'pending'
        ]);

        // 🚨 Start local security tests
        $xss     = $this->testXSS($request->url);
        $sql     = $this->testSQL($request->url);
        $headers = $this->testHeaders($request->url);

        // 🤖 AI Analysis (new part)
        $analysis = $ai->analyze(
            xss: $xss,
            sql: $sql,
            headers: $headers,
            url: $request->url
        );

        // 🗂 Save results in database
        ScanResult::create([
            'scan_id'        => $scan->id,
            'xss_result'     => $xss,
            'sql_result'     => $sql,
            'headers_result' => $headers,
            'ai_analysis'    => $analysis
        ]);

        // 🔄 Update scan status
        $scan->update(['status' => 'completed']);

        // 📤 Return response
        return response()->json([
            'message' => 'Scan and AI analysis completed successfully',
            'scan_id' => $scan->id
        ]);
    }


//كود فحص XSS
    private function testXSS($url)
{
    $payload = "<script>alert('XSS')</script>";

    $response = Http::get($url . "?test=" . urlencode($payload));

    if (strpos($response->body(), $payload) !== false) {
        return "Vulnerable: XSS detected";
    } else {
        return "Safe: No XSS found";
    }
}

//كود فحص SQL Injection
private function testSQL($url)
{
    $payload = "' OR 1=1 --";

    $response = Http::get($url . "?id=" . urlencode($payload));

    if (strpos($response->body(), "SQL") !== false ||
        strpos($response->body(), "syntax") !== false) {
        return "Vulnerable: SQL Injection indicators found";
    } else {
        return "Safe: No SQLi found";
    }
}



//فحص الهيدرز الأمنية
private function testHeaders($url)
{
    $response = Http::get($url);

    $missing = [];

    $required = [
        "X-Frame-Options",
        "Content-Security-Policy",
        "X-XSS-Protection"
    ];

    foreach ($required as $header) {
        if (!$response->header($header)) {
            $missing[] = $header;
        }
    }

    if (count($missing) > 0) {
        return "Missing Security Headers: " . implode(", ", $missing);
    } else {
        return "All recommended security headers are present";
    }
}



public function getScan(Request $request, $id)
{
    $scan = Scan::where('id', $id)
        ->where('user_id', $request->user()->id)
        ->with('results')
        ->first();

    if (!$scan) {
        return response()->json(['message' => 'Scan not found'], 404);
    }

    return response()->json([
        'scan' => [
            'id' => $scan->id,
            'url' => $scan->url,
            'status' => $scan->status,
            'pdf_path' => $scan->pdf_path,
            'results' => [
                'xss_result' => $scan->results->xss_result,
                'sql_result' => $scan->results->sql_result,
                'headers_result' => $scan->results->headers_result,
                'ai_analysis' => $scan->results->ai_analysis,
            ]
        ]
    ]);
}



//دالة تحميل pdf
public function generatePDF($id)
{
    $scan = Scan::with('results')->find($id);

    if (!$scan) {
        return response()->json(['message' => 'Scan not found'], 404);
    }

    $pdf = Pdf::loadView('pdf.report', ['scan' => $scan]);

    $filename = 'scan_report_' . $scan->id . '_' . time() . '.pdf';
    $path = 'reports/' . $filename;

    // حفظ PDF داخل السيرفر
    $pdf->save(public_path($path));

    // حفظ المسار في قاعدة البيانات
    $scan->update(['pdf_path' => $path]);

    return response()->json([
    'message'  => 'Report generated successfully',
    'pdf_path' => $path,       // ← الحل
    'pdf_url'  => url($path)   // ← قيمة إضافية اختيارية
]);

}


//إنشاء Dashboard لعرض الفحوصات
public function listScans(Request $request)
{
    $scans = Scan::where('user_id', $request->user()->id)
                ->with('results')
                ->orderBy('id', 'desc')
                ->paginate(10); // Pagination

    return response()->json($scans, 200);
}


public function deleteScan($id)
{
    $scan = Scan::find($id);

    if (!$scan) {
        return response()->json(['message' => 'Scan not found'], 404);
    }

    // حذف تقرير PDF إن وُجد
    if ($scan->pdf_path && file_exists(public_path($scan->pdf_path))) {
        unlink(public_path($scan->pdf_path));
    }

    $scan->delete();

    return response()->json(['message' => 'Scan deleted successfully'], 200);
}

//إضافة إمكانية إعادة الفحص (Rescan)
public function rescan($id, Request $request, AIService $ai)
{
    $scan = Scan::with('results')->find($id);

    if (!$scan) {
        return response()->json(['message' => 'Scan not found'], 404);
    }

    $url = $scan->url;

    // تحديث الحالة
    $scan->update(['status' => 'pending']);

    // إعادة الفحص
    $xss = $this->testXSS($url);
    $sql = $this->testSQL($url);
    $headers = $this->testHeaders($url);

    // تحليل بالذكاء الاصطناعي
    $analysis = $ai->analyze($xss, $sql, $headers, $url);

    // تحديث النتائج القديمة
    $scan->results->update([
        'xss_result'    => $xss,
        'sql_result'    => $sql,
        'headers_result'=> $headers,
        'ai_analysis'   => $analysis
    ]);

    // وضع الحالة مكتملة
    $scan->update(['status' => 'completed']);

    return response()->json(['message' => 'Scan re-evaluated successfully'], 200);
}


}

