<?php

namespace app\controllers;

use app\models\Task;
use app\models\Category;
use yii\web\Controller;
use app\models\Categories;
use yii\helpers\ArrayHelper;
use Yii;

class TasksController extends Controller
{
    public function actionIndex()
    {
        $request = Yii::$app->request;
        $categories = new Categories();
        $selectedCategoriesId = Categories::CATEGORIES_NOT_SELECTED;
        $additionalCondition = Categories::NO_ADDITION_SELECTED;
        $period = '';
        if ($request->isPost) {
            $categories->load(Yii::$app->request->post());
            if ($categories->validate()) {
                if ($categories->categoriesCheckArray !== Categories::CATEGORIES_NOT_SELECTED) {
                    $selectedCategoriesId = array_values($categories->categoriesCheckArray);
                }
                if ($categories->additionCategoryCheck !== Categories::NO_ADDITION_SELECTED) {
                    $additionalCondition = $categories->additionCategoryCheck;
                }
                if ($categories->period != null) {
                    $period = $categories->period;
                }
            }
        }
        $query = Task::find()->select([
            'tasks.name',
            'tasks.budget',
            'cities.name as city',
            'locations.street as street',
            'categories.name as category',
            'add_date',
        ]);
        if ($selectedCategoriesId === Categories::CATEGORIES_NOT_SELECTED) {
            if ($additionalCondition === Categories::NO_ADDITION_SELECTED) {
                $query = $query->where(['tasks.status' => 'new']);
            } else {
                $query = $query->where(['tasks.status' => 'new', 'contr_id' => 0]);
            }
        } else {
            if ($additionalCondition === Categories::NO_ADDITION_SELECTED) {
                foreach ($selectedCategoriesId as $catId) {
                    $query = $query->orWhere(['tasks.status' => 'new', 'cat_id' => "$catId"]);
                }
            } else {
                foreach ($selectedCategoriesId as $catId) {
                    $query = $query->orWhere(
                        [
                            'tasks.status' => 'new',
                            'contr_id' => 0,
                            'cat_id' => "$catId"
                        ]
                    );
                }
            }
        }
        $query = $query->
            innerJoin('locations', 'loc_id = locations.id')->
            innerJoin('categories', 'cat_id = categories.id')->
            innerJoin('cities', 'cities.id = locations.city_id');
        if (strlen($period) > 0) {
            $hours = [1, 12, 24];
            $date = date("Y-m-d H:i:s", time() - 3600 * $hours[$period]);
            $query = $query->andWhere(['>', 'add_date', "$date"]);
        }
        $query = $query->limit(3)->offset(0)->orderBy(['add_date' => SORT_DESC]);
        $tasks = $query->all();

        $cats = Category::find()->select("*")->all();
        $categoryNames[Categories::MAIN_CATEGORIES] = ArrayHelper::map($cats, 'id', 'name');
        $categoryNames[Categories::ADD_CONDITION] = 'Без исполнителя';
        $categoryNames[Categories::PERIOD] = [
            '1 час',
            '12 часов',
            '24 часа'
        ];
        return $this->render('index', [
            'tasks' => $tasks,
            'categories' => $categories,
            'categoryNames' => $categoryNames,
        ]);
    }
}
