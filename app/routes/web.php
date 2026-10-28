<?php

use App\Http\Controllers\Auth;
use App\Http\Controllers\FileController;
use App\Http\Controllers\Patient;
use App\Http\Controllers\Pro;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\PushController;
use Illuminate\Support\Facades\Route;

/*
| Rotas do Saúde App. O paciente usa o app (celular) na raiz; o profissional usa o painel em /painel.
| Toda rota com dado de paciente passa por autenticação, tempo de sessão e checagem de acesso.
*/

Route::get('/', [PublicController::class, 'root'])->name('root');
Route::get('/bem-vindo', [PublicController::class, 'onboarding'])->name('onboarding');
Route::get('/aviso-de-privacidade', [PublicController::class, 'notice'])->name('privacy.notice');
Route::get('/sobre', [PublicController::class, 'about'])->name('about');

// ---------- Entrada ----------
Route::middleware('guest')->group(function () {
    Route::get('/entrar', [Auth\LoginController::class, 'show'])->name('login');
    Route::post('/entrar', [Auth\LoginController::class, 'login'])->middleware('throttle:login');
    Route::get('/recuperar-senha', [Auth\PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/recuperar-senha', [Auth\PasswordResetController::class, 'send'])->middleware('throttle:login');
    Route::get('/nova-senha/{token}', [Auth\PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/nova-senha', [Auth\PasswordResetController::class, 'update'])->name('password.update')->middleware('throttle:login');

    Route::get('/primeiro-acesso', [Auth\FirstAccessController::class, 'code'])->name('first');
    Route::post('/primeiro-acesso', [Auth\FirstAccessController::class, 'checkCode'])->middleware('throttle:login');
    Route::get('/primeiro-acesso/senha', [Auth\FirstAccessController::class, 'password'])->name('first.password');
    Route::post('/primeiro-acesso/senha', [Auth\FirstAccessController::class, 'savePassword']);
    Route::get('/primeiro-acesso/dados', [Auth\FirstAccessController::class, 'review'])->name('first.review');
    Route::post('/primeiro-acesso/dados', [Auth\FirstAccessController::class, 'finish']);

    Route::get('/configurar/{token}', [Auth\SetupController::class, 'show'])->name('setup');
    Route::post('/configurar/{token}', [Auth\SetupController::class, 'store'])->middleware('throttle:login');
});

Route::middleware('auth')->group(function () {
    Route::post('/sair', [Auth\LoginController::class, 'logout'])->name('logout');
    Route::post('/push/assinar', [PushController::class, 'subscribe'])->middleware(['timeouts', 'throttle:sensitive'])->name('push.subscribe');
    Route::post('/push/cancelar', [PushController::class, 'unsubscribe'])->middleware('timeouts')->name('push.unsubscribe');
    Route::get('/2fa/configurar', [Auth\TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::post('/2fa/configurar', [Auth\TwoFactorController::class, 'confirm'])->middleware('throttle:sensitive');
    Route::get('/2fa/codigos', [Auth\TwoFactorController::class, 'codes'])->name('2fa.codes');
    Route::get('/2fa', [Auth\TwoFactorController::class, 'challenge'])->name('2fa.challenge');
    Route::post('/2fa', [Auth\TwoFactorController::class, 'verify'])->middleware('throttle:sensitive');
    Route::get('/confirmar-senha', [Auth\ReauthController::class, 'show'])->name('reauth');
    Route::post('/confirmar-senha', [Auth\ReauthController::class, 'confirm'])->middleware('throttle:sensitive');
});

// Arquivos (laudos, fotos, exames, planos): mesma rota para paciente e equipe, com checagem de acesso.
Route::get('/arquivos/{file}', [FileController::class, 'show'])->middleware(['auth', 'timeouts', '2fa'])->name('files.show');

// ---------- App do paciente ----------
Route::middleware(['auth', 'user.type:patient', 'timeouts'])->group(function () {
    Route::get('/aviso/revisar', [Patient\ProfileController::class, 'noticeReview'])->name('notice.review');
    Route::post('/aviso/revisar', [Patient\ProfileController::class, 'noticeAccept'])->name('notice.accept');

    Route::middleware('patient.ready')->group(function () {
        Route::get('/inicio', [Patient\HomeController::class, 'index'])->name('home');
        Route::get('/registrar', [Patient\HomeController::class, 'quick'])->name('quick');
        Route::post('/inicio/instalar', [Patient\HomeController::class, 'dismissInstall'])->name('install.dismiss');

        Route::middleware('module:metas|sintomas')->group(function () {
            Route::get('/metas', [Patient\HabitsController::class, 'index'])->name('habits');
        });
        Route::middleware('module:metas')->group(function () {
            Route::post('/metas/agua', [Patient\HabitsController::class, 'water'])->name('habits.water');
            Route::post('/metas/proteina', [Patient\HabitsController::class, 'protein'])->name('habits.protein');
            Route::post('/metas/vegetais', [Patient\HabitsController::class, 'fvl'])->name('habits.fvl');
        });

        Route::middleware('module:sintomas')->group(function () {
            Route::get('/sintomas', [Patient\SymptomsController::class, 'index'])->name('symptoms');
            Route::post('/sintomas', [Patient\SymptomsController::class, 'store']);
            Route::post('/sintomas/estou-bem', [Patient\SymptomsController::class, 'fine'])->name('symptoms.fine');
        });

        Route::get('/evolucao', [Patient\EvolutionController::class, 'index'])->middleware('module:bioimpedancia|fotos|exames')->name('evolution');

        Route::middleware('module:bioimpedancia')->group(function () {
            Route::get('/bioimpedancia', [Patient\BioController::class, 'index'])->name('bio.index');
            Route::get('/bioimpedancia/nova', [Patient\BioController::class, 'create'])->name('bio.create');
            Route::post('/bioimpedancia', [Patient\BioController::class, 'store'])->middleware('throttle:uploads')->name('bio.store');
            Route::get('/bioimpedancia/{record}', [Patient\BioController::class, 'show'])->name('bio.show');
            Route::get('/bioimpedancia/{record}/salva', [Patient\BioController::class, 'done'])->name('bio.done');
            Route::delete('/bioimpedancia/{record}', [Patient\BioController::class, 'destroy'])->name('bio.destroy');
        });

        Route::middleware('module:fotos')->group(function () {
            Route::get('/fotos/nova', [Patient\PhotosController::class, 'create'])->name('photos.create');
            Route::post('/fotos', [Patient\PhotosController::class, 'store'])->middleware('throttle:uploads')->name('photos.store');
            Route::get('/fotos/comparar', [Patient\PhotosController::class, 'compare'])->name('photos.compare');
            Route::post('/fotos/consentimento', [Patient\PhotosController::class, 'consent'])->name('photos.consent');
            Route::delete('/fotos/{date}', [Patient\PhotosController::class, 'destroy'])->name('photos.destroy');
        });

        Route::middleware('module:exames')->group(function () {
            Route::get('/exames/novo', [Patient\ExamsController::class, 'create'])->name('exams.create');
            Route::post('/exames', [Patient\ExamsController::class, 'store'])->middleware('throttle:uploads')->name('exams.store');
        });

        Route::get('/cuidado', [Patient\CareController::class, 'index'])->middleware('module:nutricao|atividade|saude_mental')->name('care');

        Route::middleware('module:nutricao')->group(function () {
            Route::get('/nutricao', [Patient\CareController::class, 'nutrition'])->name('nutrition');
            Route::post('/nutricao/plano', [Patient\CareController::class, 'uploadPlan'])->middleware('throttle:uploads')->name('nutrition.plan');
        });

        Route::middleware('module:atividade')->group(function () {
            Route::get('/atividade', [Patient\ActivityController::class, 'index'])->name('activity');
            Route::get('/atividade/novo', [Patient\ActivityController::class, 'create'])->name('workouts.create');
            Route::post('/atividade', [Patient\ActivityController::class, 'store'])->name('workouts.store');
        });

        Route::middleware('module:saude_mental')->group(function () {
            Route::get('/saude-mental', [Patient\MentalController::class, 'index'])->name('mental');
            Route::post('/terapia/{session}', [Patient\MentalController::class, 'toggleTherapy'])->name('therapy.toggle');
            Route::get('/desabafos/novo', [Patient\MentalController::class, 'create'])->name('journal.create');
            Route::post('/desabafos', [Patient\MentalController::class, 'store'])->name('journal.store');
            Route::get('/desabafos/{entry}', [Patient\MentalController::class, 'show'])->name('journal.show');
            Route::post('/desabafos/{entry}/compartilhar', [Patient\MentalController::class, 'share'])->name('journal.share');
            Route::delete('/desabafos/{entry}', [Patient\MentalController::class, 'destroy'])->name('journal.destroy');
        });

        Route::get('/notificacoes', [Patient\HomeController::class, 'notifications'])->name('notifications');
        Route::post('/notificacoes/lidas', [Patient\HomeController::class, 'readAll'])->name('notifications.read');

        Route::get('/perfil', [Patient\ProfileController::class, 'index'])->name('profile');
        Route::get('/perfil/dados', [Patient\ProfileController::class, 'data'])->name('profile.data');
        Route::post('/perfil/dados', [Patient\ProfileController::class, 'saveData']);
        Route::get('/perfil/metas', [Patient\ProfileController::class, 'goals'])->middleware('module:metas')->name('profile.goals');
        Route::post('/perfil/metas', [Patient\ProfileController::class, 'saveGoals'])->middleware('module:metas');
        Route::get('/perfil/lembretes', [Patient\ProfileController::class, 'reminders'])->middleware('module:lembretes')->name('profile.reminders');
        Route::post('/perfil/lembretes', [Patient\ProfileController::class, 'saveReminder'])->middleware('module:lembretes');
        Route::get('/perfil/equipe', [Patient\ProfileController::class, 'team'])->middleware('module:equipe')->name('profile.team');
        Route::get('/perfil/aparelhos', [Patient\ProfileController::class, 'sessions'])->name('profile.sessions');
        Route::post('/perfil/aparelhos', [Patient\ProfileController::class, 'endSessions']);
        Route::get('/perfil/senha', [Patient\ProfileController::class, 'password'])->middleware('reauth')->name('profile.password');
        Route::post('/perfil/senha', [Patient\ProfileController::class, 'savePassword'])->middleware('reauth');
        Route::get('/ajuda', [Patient\ProfileController::class, 'help'])->name('help');

        // LGPD: canais sempre ligados.
        Route::get('/privacidade', [Patient\PrivacyController::class, 'index'])->name('privacy');
        Route::post('/privacidade/consentimento', [Patient\PrivacyController::class, 'consent'])->name('privacy.consent');
        Route::get('/privacidade/meus-dados', [Patient\PrivacyController::class, 'myData'])->name('privacy.data');
        Route::get('/privacidade/acessos', [Patient\PrivacyController::class, 'accesses'])->name('privacy.accesses');
        Route::post('/privacidade/exportar', [Patient\PrivacyController::class, 'export'])->middleware(['reauth', 'throttle:sensitive'])->name('privacy.export');
        Route::get('/privacidade/exportar/{token}', [Patient\PrivacyController::class, 'download'])->name('privacy.download');
        Route::get('/privacidade/pedido', [Patient\PrivacyController::class, 'requestForm'])->name('privacy.request');
        Route::post('/privacidade/pedido', [Patient\PrivacyController::class, 'storeRequest'])->middleware('throttle:sensitive');
        Route::get('/privacidade/excluir-conta', [Patient\PrivacyController::class, 'deleteForm'])->middleware('reauth')->name('privacy.delete');
        Route::post('/privacidade/excluir-conta', [Patient\PrivacyController::class, 'delete'])->middleware('reauth');
    });
});

// ---------- Painel do profissional ----------
Route::prefix('painel')->name('pro.')->middleware(['auth', 'user.type:professional', 'timeouts', '2fa', 'setup'])->group(function () {
    Route::get('/', [Pro\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/configurar', [Pro\SetupWizardController::class, 'show'])->middleware('user.type:master')->name('setup');
    Route::post('/configurar', [Pro\SetupWizardController::class, 'store'])->middleware('user.type:master')->name('setup.store');

    Route::get('/pacientes', [Pro\PatientsController::class, 'index'])->name('patients');
    Route::get('/pacientes/novo', [Pro\PatientsController::class, 'create'])->name('patients.create');
    Route::post('/pacientes', [Pro\PatientsController::class, 'store'])->name('patients.store');
    Route::get('/pacientes/{patient}', [Pro\RecordController::class, 'show'])->name('patient');
    Route::get('/pacientes/{patient}/preparar', [Pro\RecordController::class, 'prep'])->name('patient.prep');
    Route::post('/pacientes/{patient}/notas', [Pro\RecordController::class, 'note'])->name('patient.note');
    Route::post('/pacientes/{patient}/metas', [Pro\RecordController::class, 'goals'])->name('patient.goals');
    Route::post('/pacientes/{patient}/consultas', [Pro\RecordController::class, 'consultation'])->name('patient.consultation');
    Route::post('/pacientes/{patient}/plano', [Pro\RecordController::class, 'plan'])->middleware('throttle:uploads')->name('patient.plan');
    Route::post('/pacientes/{patient}/terapia', [Pro\RecordController::class, 'therapy'])->name('patient.therapy');
    Route::post('/pacientes/{patient}/lembrar', [Pro\RecordController::class, 'remind'])->name('patient.remind');
    Route::post('/pacientes/{patient}/dados', [Pro\RecordController::class, 'updateData'])->name('patient.data');
    Route::post('/pacientes/{patient}/convite', [Pro\RecordController::class, 'reinvite'])->name('patient.invite');
    Route::post('/pacientes/{patient}/modulos', [Pro\RecordController::class, 'modules'])->middleware('user.type:master')->name('patient.modules');
    Route::post('/pacientes/{patient}/equipe', [Pro\RecordController::class, 'team'])->middleware('user.type:master')->name('patient.team');

    Route::get('/alertas', [Pro\AlertsController::class, 'index'])->name('alerts');
    Route::post('/alertas/{alert}/revisar', [Pro\AlertsController::class, 'resolve'])->name('alerts.resolve');

    Route::get('/desabafos', [Pro\JournalsController::class, 'index'])->name('journals');
    Route::post('/desabafos/{entry}/levar', [Pro\JournalsController::class, 'toNotes'])->name('journals.note');

    Route::get('/agenda', [Pro\AgendaController::class, 'index'])->name('agenda');
    Route::post('/agenda', [Pro\AgendaController::class, 'store'])->name('agenda.store');
    Route::post('/agenda/{appointment}', [Pro\AgendaController::class, 'status'])->name('agenda.status');

    Route::get('/conta', [Pro\AccountController::class, 'index'])->name('account');
    Route::post('/conta/aparelhos', [Pro\AccountController::class, 'endSessions'])->name('account.sessions');
    Route::match(['get', 'post'], '/conta/senha', [Pro\AccountController::class, 'password'])->middleware('reauth')->name('account.password');

    Route::get('/configuracoes', [Pro\SettingsController::class, 'index'])->name('settings');
    Route::middleware('user.type:master')->group(function () {
        Route::post('/configuracoes/modulos', [Pro\SettingsController::class, 'modules'])->name('settings.modules');
        Route::post('/configuracoes/metas', [Pro\SettingsController::class, 'goalToggles'])->name('settings.goals');
        Route::post('/configuracoes/regras', [Pro\SettingsController::class, 'rules'])->name('settings.rules');
        Route::post('/configuracoes/padrao', [Pro\SettingsController::class, 'defaults'])->name('settings.defaults');
        Route::post('/configuracoes/consultorio', [Pro\SettingsController::class, 'clinic'])->name('settings.clinic');
        Route::post('/configuracoes/equipe', [Pro\SettingsController::class, 'inviteMember'])->name('settings.team');
        Route::post('/configuracoes/equipe/{user}', [Pro\SettingsController::class, 'memberStatus'])->name('settings.member');
        Route::post('/configuracoes/aviso', [Pro\SettingsController::class, 'publishNotice'])->name('settings.notice');

        Route::get('/auditoria', [Pro\ComplianceController::class, 'audit'])->name('audit');
        Route::get('/pedidos', [Pro\ComplianceController::class, 'requests'])->name('requests');
        Route::post('/pedidos/{dsr}', [Pro\ComplianceController::class, 'closeRequest'])->name('requests.close');
        Route::get('/incidentes', [Pro\ComplianceController::class, 'incidents'])->name('incidents');
        Route::post('/incidentes', [Pro\ComplianceController::class, 'storeIncident'])->name('incidents.store');
    });
});
