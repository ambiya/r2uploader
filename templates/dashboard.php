<div class="breadcrumbs">
  <a href="/">🏠 <?= __('home_title') ?></a>
  <span>/</span> <span><?= __('nav_dashboard') ?></span>
</div>

<h2 style="font-size:1.5rem; margin-bottom:1.5rem; display:flex; align-items:center; gap:0.5rem;">
  <svg style="width:1.5rem;height:1.5rem;color:var(--accent);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
  <?= __('dashboard_analytics') ?>
</h2>

<?php if (!$isConfigured): ?>
<div class="card error-card error-card--config" style="margin-bottom:1.5rem;">
  <h3><?= __('config_incomplete') ?></h3>
  <p><?= __('config_incomplete_desc') ?></p>
</div>
<?php endif; ?>

<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:1.5rem; margin-bottom:2rem;">
  <!-- Live R2 Stats -->
  <?php foreach ($r2Stats as $bucketName => $stats): ?>
  <div class="card" style="margin-bottom:0;">
    <h3 style="margin-bottom:1rem; font-size:1.1rem; display:flex; align-items:center; justify-content:space-between;">
      <span><?= __('bucket_label') ?><?= htmlspecialchars($bucketName) ?></span>
      <span class="badge"><?= strtoupper(htmlspecialchars($stats['type'] ?? '')) ?></span>
    </h3>
    
    <?php if (isset($stats['error'])): ?>
      <p style="color:var(--danger); font-size:0.9rem;"><?= htmlspecialchars($stats['error']) ?></p>
    <?php else: ?>
      <div style="display:flex; justify-content:space-around; text-align:center; margin-bottom:1.5rem;">
        <div>
          <div style="font-size:2rem; font-weight:700; color:var(--accent);"><?= $stats['totalFiles'] ?></div>
          <div style="font-size:0.85rem; color:var(--text-muted); text-transform:uppercase;"><?= __('total_files') ?></div>
        </div>
        <div>
          <div style="font-size:2rem; font-weight:700; color:var(--success);"><?= $stats['totalSize'] ?></div>
          <div style="font-size:0.85rem; color:var(--text-muted); text-transform:uppercase; display:flex; align-items:center; justify-content:center; gap:0.25rem;">
            <?= __('total_size') ?>
            <span title="<?= __('tooltip_billable_size') ?>" style="cursor:help;">
              <svg style="width:1rem;height:1rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </span>
          </div>
        </div>
      </div>

      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap:1.5rem; border-top: 1px solid var(--border-light); padding-top:1.25rem;">
         <!-- Top 5 Files -->
         <div>
            <h4 style="font-size:0.95rem; margin-bottom:0.75rem; color:var(--text); display:flex; align-items:center; gap:0.25rem;">
                <svg style="width:1.2rem;height:1.2rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                <?= __('dashboard_top_files') ?>
            </h4>
            <?php if (empty($stats['largestFiles'])): ?>
                <p style="font-size:0.85rem; color:var(--text-muted);"><?= __('no_activity') ?></p>
            <?php else: ?>
                <ul style="list-style:none; padding:0; margin:0; font-size:0.85rem;">
                <?php foreach ($stats['largestFiles'] as $lf): ?>
                    <li style="display:flex; justify-content:space-between; margin-bottom:0.5rem; border-bottom:1px solid var(--border-light); padding-bottom:0.25rem;">
                        <span style="word-break:break-all; padding-right:0.5rem;" title="<?= htmlspecialchars($lf['Key']) ?>">
                           <?= htmlspecialchars(strlen($lf['Key']) > 25 ? substr($lf['Key'], 0, 12) . '...' . substr($lf['Key'], -10) : $lf['Key']) ?>
                        </span>
                        <strong style="white-space:nowrap; color:var(--danger);"><?= $lf['FormattedSize'] ?></strong>
                    </li>
                <?php endforeach; ?>
                </ul>
            <?php endif; ?>
         </div>
         
         <!-- File Types Distribution -->
         <div class="file-types-card" data-bucket-hash="<?= md5($bucketName) ?>" data-chart-data='<?= json_encode(array_slice($stats['fileTypes'] ?? [], 0, 6, true)) ?>'>
            <h4 style="font-size:0.95rem; margin-bottom:0.75rem; color:var(--text); display:flex; align-items:center; gap:0.25rem;">
                <svg style="width:1.2rem;height:1.2rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path><path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path></svg>
                <?= __('dashboard_file_types') ?>
            </h4>
            <?php if (empty($stats['fileTypes'])): ?>
                <p style="font-size:0.85rem; color:var(--text-muted);"><?= __('no_activity') ?></p>
            <?php else: ?>
                <div style="display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap;">
                    <div style="flex:1; min-width: 130px;">
                        <ul style="list-style:none; padding:0; margin:0; font-size:0.85rem;">
                        <?php 
                        $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];
                        $i = 0;
                        $topTypes = array_slice($stats['fileTypes'], 0, 6, true);
                        foreach ($topTypes as $ext => $count): 
                           $pct = $stats['totalFiles'] > 0 ? round(($count / $stats['totalFiles']) * 100) : 0;
                           $color = $colors[$i % count($colors)];
                           $i++;
                        ?>
                            <li style="margin-bottom:0.65rem;">
                                <div style="display:flex; justify-content:space-between; margin-bottom:0.25rem;">
                                    <strong style="text-transform:uppercase;"><?= htmlspecialchars($ext === 'unknown' ? __('unknown_type') : $ext) ?></strong>
                                    <span style="color:var(--text-muted);"><?= $count ?> (<?= $pct ?>%)</span>
                                </div>
                                <div style="width:100%; height:6px; background:var(--border-light); border-radius:3px; overflow:hidden;">
                                    <div style="height:100%; width:<?= $pct ?>%; background:<?= $color ?>; border-radius:3px;"></div>
                                </div>
                            </li>
                        <?php endforeach; ?>
                        </ul>
                    </div>
                    <div style="width:110px; height:110px; display:flex; align-items:center; justify-content:center;">
                        <canvas id="chart-types-<?= md5($bucketName) ?>" width="110" height="110"></canvas>
                    </div>
                 </div>
            <?php endif; ?>
         </div>
      </div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<!-- Weekly Activity Trends Chart -->
<div class="card" style="margin-bottom: 1.5rem;">
  <h3 style="margin-bottom: 1.25rem; font-size: 1.1rem; display: flex; align-items: center; gap: 0.5rem;">
    <svg style="width:1.25rem;height:1.25rem;color:var(--accent);" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 12l3-3 3 3 4-4M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
    <?= __('dashboard_weekly_trends') ?>
  </h3>
  <div style="height: 250px; width: 100%; position: relative;">
    <canvas id="weekly-trends-chart" data-chart-data='<?= json_encode($dailyStats) ?>'></canvas>
  </div>
</div>

<div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap:1.5rem;">
  
  <!-- User Stats -->
  <div class="card" style="margin-bottom:0;">
    <h3 style="margin-bottom:1rem; font-size:1.1rem; display:flex; align-items:center; gap:0.5rem;">
      <svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
      <?= __('user_activity') ?>
    </h3>
    
    <?php if (empty($userStats)): ?>
      <p style="color:var(--text-muted); font-size:0.9rem; font-style:italic;"><?= __('no_activity') ?></p>
    <?php else: ?>
      <table style="width:100%; font-size:0.9rem;">
        <thead>
          <tr>
            <th style="text-align:left; padding-bottom:0.5rem; border-bottom:1px solid var(--border);"><?= __('username') ?></th>
            <th style="text-align:center; padding-bottom:0.5rem; border-bottom:1px solid var(--border);"><?= __('uploads') ?></th>
            <th style="text-align:center; padding-bottom:0.5rem; border-bottom:1px solid var(--border);"><?= __('deletions') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($userStats as $stat): ?>
          <tr>
            <td style="padding:0.75rem 0; border-bottom:1px solid var(--border-light);"><strong><?= htmlspecialchars($stat['username']) ?></strong></td>
            <td style="padding:0.75rem 0; border-bottom:1px solid var(--border-light); text-align:center; color:var(--success);"><?= $stat['uploads'] ?></td>
            <td style="padding:0.75rem 0; border-bottom:1px solid var(--border-light); text-align:center; color:var(--danger);"><?= $stat['deletions'] ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
  
  <!-- Activity Log -->
  <div class="card" style="margin-bottom:0;">
    <h3 style="margin-bottom:1rem; font-size:1.1rem; display:flex; align-items:center; justify-content:space-between;">
      <div style="display:flex; align-items:center; gap:0.5rem;">
        <svg style="width:1.25rem;height:1.25rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
        <?= __('latest_activity_log') ?>
      </div>
      <?php if (!empty($activities)): ?>
      <form action="/?action=dashboard_clear_logs" method="POST" onsubmit="return confirm('<?= __('confirm_clear_logs') ?>');">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
        <button type="submit" class="btn btn--danger" style="padding: 0.25rem 0.5rem; font-size: 0.8rem;"><?= __('btn_clear_logs') ?></button>
      </form>
      <?php endif; ?>
    </h3>
    
    <?php if (empty($activities)): ?>
      <p style="color:var(--text-muted); font-size:0.9rem; font-style:italic;"><?= __('no_activity') ?></p>
    <?php else: ?>
      <div style="max-height:300px; overflow-y:auto; padding-right:0.5rem;">
        <ul style="list-style:none; padding:0; margin:0;">
          <?php foreach ($activities as $log): ?>
          <li style="margin-bottom:1rem; padding-bottom:1rem; border-bottom:1px solid var(--border-light);">
            <div style="display:flex; justify-content:space-between; margin-bottom:0.25rem;">
              <strong><?= htmlspecialchars($log['username'] ?? 'System') ?></strong>
              <span style="color:var(--text-muted); font-size:0.8rem;"><?= $log['created_at'] ?></span>
            </div>
            <div style="font-size:0.9rem;">
              <?php if ($log['action'] === 'upload'): ?>
                <span style="color:var(--success); font-weight:600;"><?= __('uploaded') ?></span> 
                <?= htmlspecialchars(basename($log['object_key'] ?? '')) ?>
              <?php elseif ($log['action'] === 'delete'): ?>
                <span style="color:var(--danger); font-weight:600;"><?= __('deleted') ?></span> 
                <?= htmlspecialchars(basename($log['object_key'] ?? '')) ?>
              <?php elseif ($log['action'] === 'rename'): ?>
                <span style="color:var(--warning); font-weight:600;"><?= __('renamed') ?></span>
              <?php endif; ?>
              
              <?php if (!empty($log['bucket'])): ?>
                <?= __('in_bucket') ?> <span class="badge"><?= htmlspecialchars($log['bucket']) ?></span>
              <?php endif; ?>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartInstances = [];

    // Helper to get CSS variable colors
    function getThemeColors() {
        const style = getComputedStyle(document.documentElement);
        return {
            accent: style.getPropertyValue('--accent').trim() || '#6366f1',
            success: style.getPropertyValue('--success').trim() || '#10b981',
            danger: style.getPropertyValue('--danger').trim() || '#ef4444',
            warning: style.getPropertyValue('--warning').trim() || '#f59e0b',
            text: style.getPropertyValue('--text-main').trim() || '#0f172a',
            muted: style.getPropertyValue('--text-muted').trim() || '#64748b',
            border: style.getPropertyValue('--border').trim() || 'rgba(226, 232, 240, 0.8)',
            borderLight: style.getPropertyValue('--border-light').trim() || 'rgba(226, 232, 240, 0.4)'
        };
    }

    let colors = getThemeColors();

    // 1. Initialize Doughnut Charts for Bucket File Types
    const typeCards = document.querySelectorAll('.file-types-card');
    typeCards.forEach(card => {
        const hash = card.getAttribute('data-bucket-hash');
        const canvasId = 'chart-types-' + hash;
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;

        const rawData = JSON.parse(card.getAttribute('data-chart-data') || '{}');
        const labels = Object.keys(rawData);
        const data = Object.values(rawData);

        const segmentColors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4'];

        const doughnutChart = new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels.map(l => l.toUpperCase()),
                datasets: [{
                    data: data,
                    backgroundColor: segmentColors,
                    borderWidth: 1,
                    borderColor: colors.border
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return ` ${context.label}: ${context.raw}`;
                            }
                        }
                    }
                },
                cutout: '65%'
            }
        });
        chartInstances.push({ chart: doughnutChart, type: 'doughnut' });
    });

    // 2. Initialize Bar Chart for Weekly Trends
    const trendsCanvas = document.getElementById('weekly-trends-chart');
    if (trendsCanvas) {
        const rawTrendsData = JSON.parse(trendsCanvas.getAttribute('data-chart-data') || '[]');
        const labels = rawTrendsData.map(item => {
            const d = new Date(item.log_date);
            return d.toLocaleDateString(document.documentElement.lang === 'id' ? 'id-ID' : 'en-US', { day: 'numeric', month: 'short' });
        });
        const uploadData = rawTrendsData.map(item => item.uploads);
        const deleteData = rawTrendsData.map(item => item.deletions);

        const trendsChart = new Chart(trendsCanvas, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: '<?= __('uploads') ?>',
                        data: uploadData,
                        backgroundColor: colors.success,
                        borderRadius: 4,
                        maxBarThickness: 25
                    },
                    {
                        label: '<?= __('deletions') ?>',
                        data: deleteData,
                        backgroundColor: colors.danger,
                        borderRadius: 4,
                        maxBarThickness: 25
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        labels: {
                            color: colors.text,
                            font: { family: 'Inter, sans-serif', weight: '500' }
                        }
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { color: colors.muted, font: { family: 'Inter, sans-serif' } }
                    },
                    y: {
                        grid: { color: colors.borderLight },
                        ticks: {
                            color: colors.muted,
                            stepSize: 1,
                            beginAtZero: true,
                            font: { family: 'Inter, sans-serif' }
                        }
                    }
                }
            }
        });
        chartInstances.push({ chart: trendsChart, type: 'trends' });
    }

    // 3. MutationObserver to handle theme toggle color updates
    const observer = new MutationObserver(() => {
        const newColors = getThemeColors();
        chartInstances.forEach(item => {
            if (item.type === 'doughnut') {
                item.chart.data.datasets[0].borderColor = newColors.border;
            } else if (item.type === 'trends') {
                item.chart.options.plugins.legend.labels.color = newColors.text;
                item.chart.options.scales.x.ticks.color = newColors.muted;
                item.chart.options.scales.y.ticks.color = newColors.muted;
                item.chart.options.scales.y.grid.color = newColors.borderLight;
                item.chart.data.datasets[0].backgroundColor = newColors.success;
                item.chart.data.datasets[1].backgroundColor = newColors.danger;
            }
            item.chart.update();
        });
    });
    observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
});
</script>
