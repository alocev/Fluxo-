<?php
/**
 * FLUXO — Sistema de Gestão Doméstica
 * Gerador de Alertas Contextuais e Inteligentes
 */

require_once __DIR__ . '/functions.php';

if (!function_exists('get_system_alerts')) {
    function get_system_alerts(PDO $db, int $userId, string $mesReferencia): array {
        $alerts = [];
        $hoje = date('Y-m-d');
        $amanha = date('Y-m-d', strtotime('+1 day'));
        $mesAnterior = get_previous_month($mesReferencia);

        // 1. VERIFICAÇÃO DO ORÇAMENTO GERAL DO MÊS
        $stmtOrcamento = $db->prepare("SELECT limite_mensal FROM orcamentos WHERE usuario_id = :uid AND mes_referencia = :mes");
        $stmtOrcamento->execute([':uid' => $userId, ':mes' => $mesReferencia]);
        $orcamento = $stmtOrcamento->fetch();

        $stmtTotal = $db->prepare("SELECT COALESCE(SUM(valor), 0) AS total_gasto FROM contas WHERE usuario_id = :uid AND mes_referencia = :mes");
        $stmtTotal->execute([':uid' => $userId, ':mes' => $mesReferencia]);
        $totalGasto = (float)$stmtTotal->fetchColumn();

        if ($orcamento) {
            $limite = (float)$orcamento['limite_mensal'];
            if ($limite > 0) {
                $pct = ($totalGasto / $limite) * 100;
                $disponivel = $limite - $totalGasto;

                if ($totalGasto > $limite) {
                    $alerts[] = [
                        'type' => 'danger',
                        'icon' => '🚨',
                        'title' => 'Orçamento Ultrapassado',
                        'message' => 'O orçamento de ' . get_month_name_only($mesReferencia) . ' foi ultrapassado em ' . format_currency(abs($disponivel)) . ' (' . round($pct) . '% utilizado).'
                    ];
                } elseif ($pct >= 80) {
                    $alerts[] = [
                        'type' => 'warning',
                        'icon' => '⚠️',
                        'title' => 'Atenção ao Orçamento',
                        'message' => 'Você está próximo de atingir seu orçamento mensal (' . round($pct) . '% utilizado). Restam ' . format_currency($disponivel) . ' disponíveis.'
                    ];
                }
            }
        }

        // 2. LIMITES INDIVIDUAIS DE CONTAS DO MÊS
        $stmtLimites = $db->prepare("
            SELECT nome, categoria, valor, limite_gasto 
            FROM contas 
            WHERE usuario_id = :uid AND mes_referencia = :mes AND limite_gasto IS NOT NULL AND limite_gasto > 0
        ");
        $stmtLimites->execute([':uid' => $userId, ':mes' => $mesReferencia]);
        $contasComLimite = $stmtLimites->fetchAll();

        foreach ($contasComLimite as $c) {
            $meta = get_category_meta($c['categoria']);
            $val = (float)$c['valor'];
            $lim = (float)$c['limite_gasto'];
            $pctConta = ($val / $lim) * 100;

            if ($val > $lim) {
                $alerts[] = [
                    'type' => 'danger',
                    'icon' => $meta['icon'],
                    'title' => 'Limite Ultrapassado',
                    'message' => "{$meta['icon']} {$c['nome']} ultrapassou o limite definido (" . format_currency($val) . " de " . format_currency($lim) . ")."
                ];
            } elseif ($pctConta >= 80) {
                $alerts[] = [
                    'type' => 'warning',
                    'icon' => $meta['icon'],
                    'title' => 'Atenção ao Limite',
                    'message' => "{$meta['icon']} {$c['nome']} está em " . round($pctConta) . "% do limite (" . format_currency($val) . " de " . format_currency($lim) . ")."
                ];
            }
        }

        // 3. VENCIMENTOS PRÓXIMOS OU ATRASADOS (APENAS CONTAS PENDENTES OU VENCIDAS)
        $stmtVenc = $db->prepare("
            SELECT id, nome, categoria, valor, vencimento, status 
            FROM contas 
            WHERE usuario_id = :uid AND status != 'paga' AND mes_referencia = :mes
            ORDER BY vencimento ASC
        ");
        $stmtVenc->execute([':uid' => $userId, ':mes' => $mesReferencia]);
        $pendentes = $stmtVenc->fetchAll();

        foreach ($pendentes as $p) {
            $venc = $p['vencimento'];
            $meta = get_category_meta($p['categoria']);

            if ($venc < $hoje) {
                $diasAtraso = (int)((strtotime($hoje) - strtotime($venc)) / 86400);
                $alerts[] = [
                    'type' => 'danger',
                    'icon' => '📅',
                    'title' => 'Conta Vencida',
                    'message' => "A conta {$p['nome']} (" . format_currency($p['valor']) . ") está vencida há {$diasAtraso} dia(s). Venceu em " . format_date($venc) . "."
                ];
            } elseif ($venc === $hoje) {
                $alerts[] = [
                    'type' => 'warning',
                    'icon' => '⏰',
                    'title' => 'Vence Hoje',
                    'message' => "A conta {$p['nome']} (" . format_currency($p['valor']) . ") vence hoje!"
                ];
            } elseif ($venc === $amanha) {
                $alerts[] = [
                    'type' => 'info',
                    'icon' => '📅',
                    'title' => 'Vence Amanhã',
                    'message' => "A conta {$p['nome']} (" . format_currency($p['valor']) . ") vence amanhã."
                ];
            }
        }

        // 4. VARIAÇÃO DE CONSUMO DE ÁGUA E ENERGIA
        foreach (['Água', 'Energia'] as $tipo) {
            $stmtConsumo = $db->prepare("
                SELECT mes_referencia, valor_consumo, unidade 
                FROM consumos 
                WHERE usuario_id = :uid AND tipo = :tipo AND mes_referencia IN (:mesAtual, :mesAnt)
                ORDER BY mes_referencia ASC
            ");
            $stmtConsumo->execute([
                ':uid' => $userId,
                ':tipo' => $tipo,
                ':mesAtual' => $mesReferencia,
                ':mesAnt' => $mesAnterior
            ]);
            $registros = $stmtConsumo->fetchAll();

            $valAtual = null;
            $valAnterior = null;
            $unidade = $tipo === 'Água' ? 'L' : 'kWh';

            foreach ($registros as $reg) {
                if ($reg['mes_referencia'] === $mesReferencia) {
                    $valAtual = (float)$reg['valor_consumo'];
                    $unidade = $reg['unidade'];
                } elseif ($reg['mes_referencia'] === $mesAnterior) {
                    $valAnterior = (float)$reg['valor_consumo'];
                }
            }

            if ($valAtual !== null && $valAnterior !== null && $valAnterior > 0) {
                $variacao = calc_variation($valAtual, $valAnterior);
                $icon = $tipo === 'Água' ? '💧' : '⚡';
                $nomeMesAnt = get_month_name_only($mesAnterior);

                if ($variacao['is_increase'] && $variacao['percent'] >= 10) {
                    $alerts[] = [
                        'type' => 'warning',
                        'icon' => $icon,
                        'title' => "Aumento no Consumo de {$tipo}",
                        'message' => "{$icon} O consumo de {$tipo} aumentou " . round($variacao['percent']) . "% em relação a {$nomeMesAnt} (" . format_number($valAtual) . " {$unidade} vs " . format_number($valAnterior) . " {$unidade})."
                    ];
                } elseif (!$variacao['is_increase'] && abs($variacao['percent']) >= 10) {
                    $alerts[] = [
                        'type' => 'success',
                        'icon' => $icon,
                        'title' => "Redução no Consumo de {$tipo}",
                        'message' => "{$icon} Muito bem! O consumo de {$tipo} reduziu " . round(abs($variacao['percent'])) . "% em relação a {$nomeMesAnt}."
                    ];
                }
            }
        }

        return $alerts;
    }
}
