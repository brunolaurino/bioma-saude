<?php

namespace App\Support;

/** Textos em português para as ações da trilha de auditoria. */
class AuditLabels
{
    private const MAP = [
        'record.view' => 'Abriu o prontuário',
        'record.prep' => 'Preparou a consulta',
        'record.note' => 'Escreveu uma anotação clínica',
        'record.goals' => 'Ajustou as metas',
        'record.consultation' => 'Registrou uma consulta',
        'record.plan' => 'Enviou um plano alimentar',
        'record.therapy' => 'Agendou sessão de terapia',
        'record.remind' => 'Enviou um lembrete',
        'record.data' => 'Corrigiu dados de cadastro',
        'record.team' => 'Mudou a equipe do paciente',
        'file.view' => 'Abriu um arquivo',
        'alert.resolve' => 'Revisou um alerta',
        'journal.read' => 'Leu um desabafo compartilhado',
        'journal.to_note' => 'Levou um desabafo para as anotações',
        'module.patient.enable' => 'Ligou uma função para o paciente',
        'module.patient.disable' => 'Desligou uma função para o paciente',
        'module.patient.default' => 'Voltou uma função ao padrão',
        'invite.create' => 'Gerou um convite de acesso',
        'privacy.export' => 'Gerou exportação de dados',
        'privacy.export.download' => 'Baixou a exportação de dados',
        'privacy.request' => 'Abriu um pedido de titular',
        'dsr.close' => 'Respondeu um pedido de titular',
        'consent.accept' => 'Aceitou um consentimento',
        'consent.revoke' => 'Retirou um consentimento',
        'patient.create' => 'Cadastrou o paciente',
        'patient.data.update' => 'Atualizou dados de cadastro',
        'auth.login' => 'Entrou no app',
        'auth.logout' => 'Saiu do app',
        'auth.account_created' => 'Criou a conta',
        'auth.first_access' => 'Fez o primeiro acesso',
        'auth.password.change' => 'Trocou a senha',
        'auth.reauth.fail' => 'Errou a senha ao confirmar identidade',
        'auth.sessions.end_others' => 'Saiu dos outros aparelhos',
        'auth.timeout' => 'Sessão encerrada por inatividade',
        'auth.login.fail' => 'Tentativa de entrada recusada',
        'auth.2fa.enable' => 'Ativou a verificação em duas etapas',
        'auth.2fa.fail' => 'Errou o código de verificação',
        'agenda.create' => 'Marcou uma consulta',
        'agenda.atendida' => 'Marcou consulta como atendida',
        'agenda.cancelada' => 'Cancelou uma consulta',
        'agenda.agendada' => 'Reabriu uma consulta',
        'alert.rule' => 'Mudou uma regra de alerta',
        'audit.verify' => 'Verificou a integridade da auditoria',
        'incident.create' => 'Registrou um incidente de segurança',
        'notice.publish' => 'Publicou o aviso de privacidade',
        'settings.update' => 'Mudou configurações do consultório',
        'team.block' => 'Bloqueou um profissional',
        'team.unblock' => 'Desbloqueou um profissional',
        'module.enable' => 'Ligou uma função para todos',
        'module.disable' => 'Desligou uma função para todos',
        'module.goal.enable' => 'Ligou uma meta diária',
        'module.goal.disable' => 'Desligou uma meta diária',
        'bio.delete' => 'Apagou um registro de bioimpedância',
        'photo.delete' => 'Apagou fotos de evolução',
        'journal.delete' => 'Apagou um desabafo',
        'privacy.account_closed' => 'Encerrou a conta',
    ];

    public static function label(string $action): string
    {
        return self::MAP[$action] ?? $action;
    }
}
