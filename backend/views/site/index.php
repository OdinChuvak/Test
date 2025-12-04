<?php

use common\models\Apple;
use yii\helpers\Html;
use yii\bootstrap4\Modal;

/** @var yii\web\View $this */
/** @var Apple[] $apples */

$this->title = 'Яблочный сад';
$this->params['breadcrumbs'][] = $this->title;

// CSS стили
$css = <<<CSS
.apple-container {
    min-height: 80vh;
}
.apple-card {
    transition: all 0.3s ease;
    border: 2px solid transparent;
}
.apple-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1);
}
.apple-status-on-tree {
    border-color: #28a745 !important;
    background-color: rgba(40, 167, 69, 0.05);
}
.apple-status-on-ground {
    border-color: #ffc107 !important;
    background-color: rgba(255, 193, 7, 0.05);
}
.apple-status-rotten {
    border-color: #dc3545 !important;
    background-color: rgba(220, 53, 69, 0.05);
    opacity: 0.7;
}
.apple-color-display {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: inline-block;
    border: 2px solid #ddd;
}
.apple-size-bar {
    height: 10px;
    background-color: #e9ecef;
    border-radius: 5px;
    overflow: hidden;
    margin: 10px 0;
}
.apple-size-fill {
    height: 100%;
    background-color: #28a745;
    transition: width 0.5s ease;
}
.apple-size-text {
    font-size: 12px;
    color: #6c757d;
}
.time-to-rot {
    font-size: 12px;
    color: #ffc107;
    font-weight: bold;
}
.apple-actions {
    margin-top: 10px;
}
.btn-eat {
    background-color: #28a745;
    color: white;
}
.btn-fall {
    background-color: #ffc107;
    color: #212529;
}
.btn-delete {
    background-color: #dc3545;
    color: white;
}
.btn-disabled {
    opacity: 0.5;
    cursor: not-allowed;
}
.apple-stats {
    background-color: #f8f9fa;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 20px;
}
.stat-item {
    margin-right: 20px;
    padding-right: 20px;
    border-right: 1px solid #dee2e6;
}
.stat-item:last-child {
    border-right: none;
}
.stat-value {
    font-size: 24px;
    font-weight: bold;
}
.stat-label {
    font-size: 12px;
    color: #6c757d;
    text-transform: uppercase;
}
CSS;
$this->registerCss($css);

// JavaScript для AJAX запросов
$js = <<<JS
$(document).ready(function() {
    // Обработка формы поедания яблока
    $('#send-eat-request').on('click', function(e) {
        e.preventDefault();
        
        var modal = $('#eatModal');
        var appleId = modal.data('appleId');
        var percent = $('#eat-modal-percent').val();
        
        if (!percent || percent <= 0 || percent > 100) {
            alert('Введите процент от 1 до 100!');
            return false;
        }
        
        $.ajax({
            url: '/backend/web/site/eat-apple?id=' + appleId,
            type: 'POST',
            data: {percent: percent},
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Обновляем информацию о яблоке
                    $('#apple-size-' + appleId).text(response.size + '%');
                    $('#apple-eaten-' + appleId).text(response.eaten_percent + '%');
                    $('#apple-size-fill-' + appleId).css('width', response.size + '%');
                    
                    // Обновляем кнопки
                    updateAppleButtons(appleId, response.size);
                    
                    // Показываем сообщение
                    showNotification(response.message, 'success');
                    
                    // Если яблоко полностью съедено, перезагружаем страницу
                    if (response.redirect) {
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                } else {
                    showNotification(response.message, 'error');
                }
            },
            error: function() {
                showNotification('Ошибка сервера!', 'error');
            }
        });
        
        return false;
    });
    
    // Функция обновления кнопок
    function updateAppleButtons(appleId, size) {
        var eatBtn = $('#eat-btn-' + appleId);
        var deleteBtn = $('#delete-btn-' + appleId);
        
        if (size <= 0) {
            eatBtn.addClass('btn-disabled').prop('disabled', true);
            deleteBtn.removeClass('btn-disabled').prop('disabled', false);
        }
    }
    
    // Функция показа уведомлений
    function showNotification(message, type) {
        // Создаем элемент уведомления
        var alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
        var notification = $('<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
            message +
            '<button type="button" class="close" data-dismiss="alert" aria-label="Close">' +
            '<span aria-hidden="true">&times;</span>' +
            '</button>' +
            '</div>');
        
        // Добавляем уведомление в контейнер
        $('#notifications').html(notification);
        
        // Автоматически скрываем через 5 секунд
        setTimeout(function() {
            notification.alert('close');
        }, 5000);
    }
    
    // Подтверждение удаления
    $('.btn-delete').on('click', function(e) {
        if (!confirm('Вы уверены, что хотите удалить это яблоко?')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Подтверждение падения
    $('.btn-fall').on('click', function(e) {
        if (!confirm('Уронить яблоко?')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Валидация процента
    $('.percent-input').on('input', function() {
        var value = $(this).val();
        if (value < 1) $(this).val(1);
        if (value > 100) $(this).val(100);
    });
    
    $('.btn-eat').on('click', function(e) {
        const button = $(e.target), 
            modal = $('#eatModal');
        const appleId = button.data('appleId'), 
                appleSize = button.data('appleSize');
        
        modal.data('appleId', appleId);
        
        $('#eat-modal-percent').val(Math.min(25, appleSize));
        $('#eat-modal-max').text(appleSize);
    });

    // Скрываем ошибку при изменении значения
    $('#eat-modal-percent').on('input', function() {
        $('#eat-modal-error').addClass('d-none');
    });
});
JS;
$this->registerJs($js);
?>

<div class="site-index apple-container">
    <!-- Контейнер для уведомлений -->
    <div id="notifications" style="position: fixed; top: 20px; right: 20px; z-index: 9999; width: 300px;"></div>

    <!-- Статистика -->
    <?php
    $stats = Apple::getStats();
    ?>
    <div class="apple-stats">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex">
                <div class="stat-item">
                    <div class="stat-value text-success"><?= count($stats['on_tree']) ?></div>
                    <div class="stat-label">На дереве</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value text-warning"><?= count($stats['on_ground']) ?></div>
                    <div class="stat-label">Упало</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value text-danger"><?= count($stats['rotten']) ?></div>
                    <div class="stat-label">Сгнило</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value"><?= $stats['total'] ?></div>
                    <div class="stat-label">Всего</div>
                </div>
            </div>

            <div>
                <?= Html::a('Сгенерировать яблоки', ['generate-apples'], [
                    'class' => 'btn btn-primary',
                    'data' => [
                        'confirm' => 'Сгенерировать случайные яблоки?',
                        'method' => 'post',
                    ],
                ]) ?>

                <?= Html::a('Проверить гниение', ['index'], [
                    'class' => 'btn btn-info ml-2',
                    'title' => 'Обновить статус гниения всех яблок'
                ]) ?>
            </div>
        </div>
    </div>

    <?php if (empty($apples)): ?>
        <div class="alert alert-warning text-center">
            <h4>В саду нет яблок!</h4>
            <p>Нажмите кнопку ниже, чтобы сгенерировать случайные яблоки.</p>
            <?= Html::a('Сгенерировать яблоки', ['generate-apples'], [
                'class' => 'btn btn-primary btn-lg',
                'data' => ['method' => 'post']
            ]) ?>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($apples as $apple): ?>
                <?php
                // Определяем класс статуса
                $statusClass = '';
                switch ($apple->status) {
                    case Apple::STATUS_ON_TREE:
                        $statusClass = 'apple-status-on-tree';
                        break;
                    case Apple::STATUS_ON_GROUND:
                        $statusClass = 'apple-status-on-ground';
                        break;
                    case Apple::STATUS_ROTTEN:
                        $statusClass = 'apple-status-rotten';
                        break;
                }

                // Проверяем доступность действий
                $canEat = $apple->canEat();
                $canFall = $apple->canFall();
                $canDelete = $apple->canDelete();
                ?>

                <div class="col-md-4 mb-4">
                    <div class="card apple-card <?= $statusClass ?>">
                        <div class="card-body">
                            <!-- Заголовок карточки -->
                            <div class="d-flex justify-content-between align-items-start">
                                <h5 class="card-title">
                                    Яблоко #<?= $apple->id ?>
                                </h5>
                                <span class="badge badge-<?=
                                $apple->status == Apple::STATUS_ON_TREE ? 'success' :
                                    ($apple->status == Apple::STATUS_ON_GROUND ? 'warning' : 'danger')
                                ?>">
                                    <?= $apple->statusName ?>
                                </span>
                            </div>

                            <!-- Цвет яблока -->
                            <div class="mb-3">
                                <span class="apple-color-display" style="background-color: <?= $apple->color ?>;"></span>
                                <span class="ml-2"><?= $apple->colorName ?></span>
                            </div>

                            <!-- Информация о датах -->
                            <div class="mb-3">
                                <small class="text-muted d-block">
                                    <i class="far fa-calendar-plus"></i> Появилось: <?= $apple->appearanceDateFormatted ?>
                                </small>

                                <?php if ($apple->fall_date): ?>
                                    <small class="text-muted d-block">
                                        <i class="far fa-calendar-minus"></i> Упало: <?= $apple->fallDateFormatted ?>
                                    </small>
                                <?php endif; ?>

                                <?php if ($apple->rotten_date): ?>
                                    <small class="text-muted d-block">
                                        <i class="fas fa-skull-crossbones"></i> Сгнило: <?= $apple->rottenDateFormatted ?>
                                    </small>
                                <?php endif; ?>
                            </div>

                            <!-- Прогресс-бар размера яблока -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between">
                                    <small class="apple-size-text">Размер:</small>
                                    <small class="apple-size-text">
                                        <span id="apple-size-<?= $apple->id ?>"><?= $apple->size ?></span>%
                                    </small>
                                </div>
                                <div class="apple-size-bar">
                                    <div id="apple-size-fill-<?= $apple->id ?>"
                                         class="apple-size-fill"
                                         style="width: <?= $apple->size ?>%">
                                    </div>
                                </div>
                                <small class="apple-size-text">
                                    Съедено: <span id="apple-eaten-<?= $apple->id ?>"><?= $apple->eaten_percent ?></span>%
                                </small>
                            </div>

                            <!-- Таймер до гниения (только для упавших яблок) -->
                            <?php if ($apple->status == Apple::STATUS_ON_GROUND && $apple->timeToRot): ?>
                                <div class="mb-3">
                                    <small class="time-to-rot">
                                        <i class="fas fa-clock"></i> До гниения: <?= $apple->timeToRot ?>
                                    </small>
                                </div>
                            <?php endif; ?>

                            <!-- Кнопки действий -->
                            <div class="apple-actions">
                                <div class="btn-group btn-group-sm w-100" role="group">
                                    <!-- Кнопка "Уронить" -->
                                    <?php if ($canFall): ?>
                                        <?= Html::a('<i class="fas fa-arrow-down"></i> Уронить',
                                            ['fall-apple', 'id' => $apple->id],
                                            [
                                                'class' => 'btn btn-fall',
                                                'data' => ['method' => 'post']
                                            ]
                                        ) ?>
                                    <?php else: ?>
                                        <button class="btn btn-fall btn-disabled" disabled>
                                            <i class="fas fa-arrow-down"></i> Уронить
                                        </button>
                                    <?php endif; ?>

                                    <!-- Кнопка "Съесть" -->
                                    <?php if ($canEat): ?>
                                        <button type="button"
                                                class="btn btn-eat"
                                                id="eat-btn-<?= $apple->id ?>"
                                                data-toggle="modal"
                                                data-target="#eatModal"
                                                data-apple-id="<?= $apple->id ?>"
                                                data-apple-size="<?= $apple->size ?>">
                                            <i class="fas fa-utensils"></i> Съесть
                                        </button>
                                    <?php else: ?>
                                        <button class="btn btn-eat btn-disabled" disabled>
                                            <i class="fas fa-utensils"></i> Съесть
                                        </button>
                                    <?php endif; ?>

                                    <!-- Кнопка "Удалить" -->
                                    <?php if ($canDelete): ?>
                                        <?= Html::a('<i class="fas fa-trash"></i> Удалить',
                                            ['delete-apple', 'id' => $apple->id],
                                            [
                                                'class' => 'btn btn-delete',
                                                'data' => [
                                                    'confirm' => 'Удалить это яблоко?',
                                                    'method' => 'post'
                                                ]
                                            ]
                                        ) ?>
                                    <?php else: ?>
                                        <button class="btn btn-delete btn-disabled" disabled>
                                            <i class="fas fa-trash"></i> Удалить
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Модальное окно для поедания яблока -->
<?php Modal::begin([
    'id' => 'eatModal',
    'title' => '<h4 class="modal-title">Съесть яблоко</h4>',
    'size' => 'modal-sm',
]); ?>

<div class="modal-body">
    <p>Введите процент яблока, который хотите съесть:</p>

    <div class="form-group">
        <div class="input-group">
            <input type="number"
                   id="eat-modal-percent"
                   name="percent"
                   class="form-control percent-input"
                   min="1"
                   max="100"
                   step="1"
                   required>
            <div class="input-group-append">
                <span class="input-group-text">%</span>
            </div>
        </div>
        <small class="form-text text-muted">
            Максимум можно съесть: <span id="eat-modal-max">100</span>%
        </small>
    </div>

    <div id="eat-modal-error" class="alert alert-danger d-none"></div>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary close-btn" data-dismiss="modal">Отмена</button>
    <button id="send-eat-request" type="button" class="btn btn-success">
        <i class="fas fa-utensils"></i> Съесть
    </button>
</div>

<?php Modal::end(); ?>