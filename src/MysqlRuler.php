<?php
/**
 * 根据model属性，实例化相应的规则条件
 */
namespace Validator;

use Illuminate\Support\Facades\Schema;

trait MysqlRuler
{
    use ModelRuler;
    
    public function attributes()
    {
        try {
            $table = $this->getTable();

            $columns = Schema::getColumnListing($table);
        }catch (\Exception $e) {
            return [];
        }

        return $columns;
    }

    public static function findOne($conditions)
    {
        if (is_numeric($conditions)) {
            $conditions = ['id' => intval($conditions)];
        }

        if (!is_array($conditions)) {
            throw new \Exception('findOne conditions');
        }

        return static::query()->where($conditions)->first();
    }
}
