<?php
use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use common\models\Project;
use common\models\Screen;
use common\components\helpers\CFileHelper;

/**
 * $model                \common\models\Version
 * $activeScreenId       integer|null
 * $collapseFloatingMenu boolean
 * $unreadCommentTargets array
 */

// default values
$unreadCommentTargets = isset($unreadCommentTargets) ? $unreadCommentTargets : [];
$collapseFloatingMenu = isset($collapseFloatingMenu) ? $collapseFloatingMenu : false;
$activeScreenId       = isset($activeScreenId) ? $activeScreenId : null;

if ($model->project->type == Project::TYPE_TABLET) {
    $type = 'tablet';
} elseif ($model->project->type == Project::TYPE_MOBILE) {
    $type = 'mobile';
} else {
    $type = 'desktop';
}

$generalSlideStyles = [];
if ($model->project->subtype) {
    $generalSlideStyles['width']  = Project::SUBTYPES[$model->project->subtype][0] . 'px';
    $generalSlideStyles['height'] = Project::SUBTYPES[$model->project->subtype][1] . 'px';
}

$isGuest = Yii::$app->user->isGuest;
?>

<div id="version_slider_<?= $model->id ?>"
    class="version-slider <?= $type ?>"
    data-version-id="<?= $model->id ?>"
>
    <div class="close-handle-wrapper">
        <span class="close-handle close-screen-edit"><i class="ion ion-ios-close-empty"></i></span>
    </div>

    <nav class="floating-menu <?= $collapseFloatingMenu ? 'collapsed' : '' ?>">
        <ul>
            <li id="fm_hotspots_handle" class="menu-item hotspots-handle active" data-cursor-tooltip="<?= Yii::t('app', 'Hotspots mode') ?>" data-cursor-tooltip-class="hotspots-mode-tooltip"><i class="ion ion-ios-crop"></i></li>
            <li id="fm_comments_handle" class="menu-item comments-handle" data-cursor-tooltip="<?= Yii::t('app', 'Comments mode') ?>" data-cursor-tooltip-class="comments-mode-tooltip">
                <i class="ion ion-ios-chatboxes-outline"></i>
                <span class="bubble comments-counter">0</span>
            </li>
<!--             <li class="menu-item comments-nav">
                <span class="nav-handle prev-handle" data-cursor-tooltip="<?= Yii::t('app', 'Prev comment') ?>" data-cursor-tooltip-class="comments-mode-tooltip"><i class="ion ion-android-arrow-back"></i></span>
                <span class="nav-handle next-handle" data-cursor-tooltip="<?= Yii::t('app', 'Next comment') ?>" data-cursor-tooltip-class="comments-mode-tooltip"><i class="ion ion-android-arrow-forward"></i></span>
            </li> -->
            <li id="fm_settings_handle" class="menu-item settings-handle" data-cursor-tooltip="<?= Yii::t('app', 'Screen settings') ?>"><i class="ion ion-ios-gear-outline"></i></li>
            <li id="fm_visibility_handle" class="menu-item visibility-handle" data-collapsed-text="<?= Yii::t('app', 'Menu') ?>" data-expanded-text="<?= Yii::t('app', 'Hide') ?>"></li>
        </ul>
    </nav>

    <div class="slider-items">
        <?php foreach ($model->screens as $i => $screen): ?>
            <?php
                if ($activeScreenId === null && $i === 0) {
                    $isActive = true;
                } else {
                    $isActive = $activeScreenId !== null && $activeScreenId == $screen->id;
                }

                // alignment
                if ($screen->alignment == Screen::ALIGNMENT_LEFT) {
                    $align = 'left';
                } elseif ($screen->alignment == Screen::ALIGNMENT_RIGHT) {
                    $align = 'right';
                } else {
                    $align = 'center';
                }

                $background = ($screen->background ? $screen->background : '#eff2f8');

                // image dimensions
                $width  = 0;
                $height = 0;
                if (file_exists(CFileHelper::getPathFromUrl($screen->imageUrl))) {
                    list($width, $height) = getimagesize(CFileHelper::getPathFromUrl($screen->imageUrl));
                }

                // hotspots
                $hotspots = $screen->hotspots ? json_decode($screen->hotspots, true) : [];
            ?>
            <div class="slider-item screen <?= $isActive ? 'active' : ''?>"
                data-screen-id="<?= $screen->id ?>"
                data-alignment="<?= $align ?>"
                data-title="<?= Html::encode($screen->title) ?>"
                style="<?= Html::cssStyleFromArray(array_merge($generalSlideStyles, ['background' => $background])) ?>"
            >
                <figure class="img-wrapper hotspot-layer-wrapper">
                    <img class="img lazy-load hotspot-layer"
                        alt="<?= Html::encode($screen->title) ?>"
                        width="<?= $width ?>px"
                        height="<?= $height ?>px"
                        data-src="<?= $screen->imageUrl ?>"
                        data-priority="<?= $isActive ? 'high' : 'medium' ?>"
                    >

                    <!-- Hotspots -->
                    <div id="hotspots_wrapper">
                        <?php foreach ($hotspots as $id => $spot): ?>
                            <div id="<?= Html::encode($id) ?>"
                                class="hotspot"
                                data-context-menu="#hotspot_context_menu"
                                data-link="<?= Html::encode(ArrayHelper::getValue($spot, 'link', '')); ?>"
                                style="width: <?= Html::encode(ArrayHelper::getValue($spot, 'width', 0)); ?>px; height: <?= Html::encode(ArrayHelper::getValue($spot, 'height', 0)); ?>px; top: <?= Html::encode(ArrayHelper::getValue($spot, 'top', 0)); ?>px; left: <?= Html::encode(ArrayHelper::getValue($spot, 'left', 0)); ?>px"
                            >
                                <span class="remove-handle context-menu-ignore"><i class="ion ion-trash-a"></i></span>
                                <span class="resize-handle context-menu-ignore"></span>
                            </div>
                        <?php endforeach ?>
                    </div>

                    <!-- Comment targets -->
                    <div id="comment_targets_list" class="comment-targets-list">
                        <?php foreach ($screen->screenComments as $comment): ?>
                            <?php if (!$comment->replyTo): ?>
                                <div class="comment-target <?= (in_array($comment->id, $unreadCommentTargets)) ? 'unread' : '' ?>"
                                    data-comment-id="<?= $comment->id ?>"
                                    style="left: <?= Html::encode($comment->posX) ?>px; top: <?= Html::encode($comment->posY) ?>px;"
                                ></div>
                            <?php endif ?>
                        <?php endforeach ?>
                    </div>
                </figure>
            </div>
        <?php endforeach ?>
    </div>

    <?= $this->render('_hotspots_popover', ['screens' => $model->screens]); ?>

    <?= $this->render('_comments_popover'); ?>

    <div id="hotspots_bulk_panel" class="fixed-panel hotspots-bulk-panel" style="display: none;">
        <span class="close hotspots-bulk-reset"><i class="ion ion-close"></i></span>

        <div class="table-wrapper">
            <div class="table-cell min-width">
                <button id="hotspots_bulk_screens_select" type="button" class="btn btn-sm btn-primary btn-ghost hotspots-bulk-screens-btn">
                    <?= Yii::t('app', 'Duplicate on screen') ?>
                    <i class="ion ion-android-arrow-dropdown m-l-5"></i>

                    <div id="hotspots_bulk_screens_popover" class="popover hotspots-bulk-screens-popover bottom-left">
                        <div class="popover-thumbs-wrapper">
                            <?php foreach ($model->screens as $screen): ?>
                                <div class="box popover-thumb" data-screen-id="<?= $screen->id ?>" data-cursor-tooltip="<?= Html::encode($screen->title) ?>">
                                    <div class="content">
                                        <figure class="featured">
                                            <img class="img lazy-load"
                                                alt="<?= Html::encode($screen->title) ?>"
                                                data-src="<?= $screen->getThumbUrl('small') ?>"
                                                data-priority="low"
                                            >
                                        </figure>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                </button>
            </div>
            <div class="table-cell min-width p-l-15 p-r-15"><?= Yii::t('app', 'or') ?></div>
            <div class="table-cell min-width">
                <a href="#" id="hotspots_bulk_delete" class="danger-link"><?= Yii::t('app', 'Delete selected') ?></a>
            </div>
            <div class="table-cell text-right">
                <a href="#" class="hotspots-bulk-reset"><?= Yii::t('app', 'Reset selection') ?></a>
            </div>
        </div>
    </div>
</div>