<?php

namespace CachetHQ\Cachet\Models;

use CachetHQ\Cachet\Transformers\MetricTransformer;
use DateInterval;
use DateTime;
use Dingo\Api\Transformer\TransformableInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Watson\Validating\ValidatingTrait;

/**
 * @property int            $id
 * @property string         $name
 * @property string         $suffix
 * @property string         $description
 * @property int            $display_chart
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Metric extends Model implements TransformableInterface
{
    use ValidatingTrait;

    const CALC_SUM = 0;
    const CALC_AVG = 1;

    /**
     * The model's attributes.
     *
     * @var string[]
     */
    protected $attributes = [
        'name'          => '',
        'display_chart' => 1,
        'default_value' => 0,
        'calc_type'     => 0,
    ];

    /**
     * The validation rules.
     *
     * @var string[]
     */
    protected $rules = [
        'name'          => 'required',
        'suffix'        => 'required',
        'display_chart' => 'boolean',
        'default_value' => 'numeric',
    ];

    /**
     * The fillable properties.
     *
     * @var string[]
     */
    protected $fillable = ['name', 'suffix', 'description', 'display_chart', 'default_value', 'calc_type'];

    /**
     * Metrics contain many metric points.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function points()
    {
        return $this->hasMany('CachetHQ\Cachet\Models\MetricPoint', 'metric_id', 'id');
    }

    /**
     * Returns the sum of all values a metric has.
     *
     * @param int $hour
     *
     * @return int
     */
    public function getValues($hour)
    {
        $dateTime = new DateTime();
        $dateTime->sub(new DateInterval('PT'.$hour.'H'));

        if (Config::get('database.default') === 'mysql') {
            $hour += 2;
            if (! isset($this->calc_type) || $this->calc_type == self::CALC_SUM) {
                $value = (int) $this->points()->whereRaw('DATE_FORMAT(created_at, "%Y%m%d%H") = '.$dateTime->format('YmdH'))->whereRaw('HOUR(created_at) = HOUR(DATE_SUB(NOW(), INTERVAL '.$hour.' HOUR))')->groupBy(DB::raw('HOUR(created_at)'))->sum('value');
            } elseif ($this->calc_type == self::CALC_AVG) {
                $value = (int) $this->points()->whereRaw('DATE_FORMAT(created_at, "%Y%m%d%H") = '.$dateTime->format('YmdH'))->whereRaw('HOUR(created_at) = HOUR(DATE_SUB(NOW(), INTERVAL '.$hour.' HOUR))')->groupBy(DB::raw('HOUR(created_at)'))->avg('value');
            }
        } else {
            // Default metrics calculations.
            if (! isset($this->calc_type) || $this->calc_type === self::CALC_SUM) {
                $queryType = "sum(metric_points.value)";
            } elseif ($this->calc_type === self::CALC_AVG) {
                $queryType = "avg(metric_points.value)";
            } else {
                $queryType = "sum(metric_points.value)";
            }

            $query = DB::select("select {$queryType} as aggregate FROM metrics JOIN metric_points ON metric_points.metric_id = metrics.id WHERE to_char(metric_points.created_at, 'YYYYMMDDHH') = :timestamp AND to_char(metric_points.created_at, 'H') = to_char(now() - interval '{$hour} hour', 'H') GROUP BY to_char(metric_points.created_at, 'H')", [
                'timestamp' => $dateTime->format('YmdH'),
            ]);

            if (isset($query[0])) {
                $value = $query[0]->aggregate;
            } else {
                $value = 0;
            }
        }

        if ($value === 0 && $this->default_value != $value) {
            return $this->default_value;
        }

        return $value;
    }

    /**
     * Determines whether a chart should be shown.
     *
     * @return bool
     */
    public function getShouldDisplayAttribute()
    {
        return $this->display_chart === 1;
    }

    /**
     * Get the transformer instance.
     *
     * @return \CachetHQ\Cachet\Transformers\MetricTransformer
     */
    public function getTransformer()
    {
        return new MetricTransformer();
    }
}
