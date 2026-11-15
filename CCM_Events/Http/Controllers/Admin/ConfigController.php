<?php
namespace Modules\CCM_Events\Http\Controllers\Admin;
use App\Contracts\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
class ConfigController extends Controller
{
    /**
     * Retourne la config actuelle (catégories + aéroports/stands).
     * Stockée en base (table settings-like du module), avec les
     * valeurs de Config/config.php comme fallback au 1er lancement.
     */
    public function index()
    {
        $categories = $this->getStoredValue('categories') ?? config('ccm_events.categories');
        $airports   = $this->getStoredValue('airports') ?? config('ccm_events.airports');
        // Pilotes pouvant être exclus des statistiques (comptes admin qui ne volent pas, etc.)
        $pilots = \App\Models\User::whereIn('state', [1, 3]) // 1 = actif, 3 = en congé
            ->orderBy('callsign')
            ->get()
            ->map(fn ($u) => [
                'id'    => $u->id,
                'label' => ($u->callsign ? 'CCM'.$u->callsign : 'Pilote #'.$u->id).' - '.($u->name_private ?? $u->name),
            ])
            ->values();

        return response()->json([
            'success'        => true,
            'categories'     => $categories,
            'airports'       => $airports,
            'stats_excluded' => $this->getStoredValue('stats_excluded_users') ?? [],
            'reminders'      => EventController::reminderSettings(),
            'pilots'         => $pilots,
        ]);
    }
    public function update(Request $request)
    {
        $validated = $request->validate([
            'categories' => 'required|array',
            'airports'   => 'required|array',
            'stats_excluded_users'   => 'nullable|array',
            'stats_excluded_users.*' => 'integer',
            'reminders'                         => 'nullable|array',
            'reminders.thresholds.reminder'     => 'required_with:reminders|integer|min:1',
            'reminders.thresholds.warning'      => 'required_with:reminders|integer|min:1',
            'reminders.thresholds.exclusion'    => 'required_with:reminders|integer|min:1',
            'reminders.mails.reminder.subject'  => 'required_with:reminders|string|max:255',
            'reminders.mails.reminder.body'     => 'required_with:reminders|string',
            'reminders.mails.warning.subject'   => 'required_with:reminders|string|max:255',
            'reminders.mails.warning.body'      => 'required_with:reminders|string',
            'reminders.mails.exclusion.subject' => 'required_with:reminders|string|max:255',
            'reminders.mails.exclusion.body'    => 'required_with:reminders|string',
        ]);
        $this->setStoredValue('categories', $validated['categories']);
        $this->setStoredValue('airports', $validated['airports']);
        if (!empty($validated['reminders'])) {
            $r = $validated['reminders'];
            $this->setStoredValue('reminders', [
                'thresholds' => [
                    'reminder'  => (int) $r['thresholds']['reminder'],
                    'warning'   => (int) $r['thresholds']['warning'],
                    'exclusion' => (int) $r['thresholds']['exclusion'],
                ],
                'mails' => [
                    'reminder' => ['subject' => $r['mails']['reminder']['subject'], 'body' => $r['mails']['reminder']['body']],
                    'warning'  => ['subject' => $r['mails']['warning']['subject'],  'body' => $r['mails']['warning']['body']],
                    'exclusion'=> ['subject' => $r['mails']['exclusion']['subject'], 'body' => $r['mails']['exclusion']['body']],
                ],
            ]);
        }
        $this->setStoredValue('stats_excluded_users', array_values(array_map('intval', $validated['stats_excluded_users'] ?? [])));
        return response()->json(['success' => true, 'message' => 'Configuration enregistrée']);
    }
    /**
     * Upload de la bannière du mois (calendrier des évènements). Le fichier
     * écrase toujours le même chemin ('event.jpg') pour ne jamais saturer
     * le dossier — un seul fichier "actif" à la fois.
     */
    public function uploadBanner(Request $request)
    {
        $request->validate([
            'banner' => 'required|image|mimes:jpg,jpeg,png|max:5120',
        ]);
        $destinationDir = public_path('SPTheme/images/event');
        if (!is_dir($destinationDir)) {
            mkdir($destinationDir, 0755, true);
        }
        $request->file('banner')->move($destinationDir, 'event.jpg');
        return response()->json(['success' => true, 'message' => 'Bannière mise à jour']);
    }
    /**
     * Supprime la bannière actuelle, si elle existe.
     */
    public function deleteBanner()
    {
        $path = public_path('SPTheme/images/event/event.jpg');

        if (file_exists($path)) {
            unlink($path);
        }

        return response()->json(['success' => true, 'message' => 'Bannière supprimée']);
    }
    protected function getStoredValue(string $key)
    {
        $row = DB::table('ccm_events_settings')->where('key', $key)->first();
        return $row ? json_decode($row->value, true) : null;
    }
    protected function setStoredValue(string $key, $value): void
    {
        DB::table('ccm_events_settings')->updateOrInsert(
            ['key' => $key],
            ['value' => json_encode($value), 'updated_at' => now()]
        );
    }
}