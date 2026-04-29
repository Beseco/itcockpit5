<?php

namespace App\Modules\OrgChart\Models;

use Illuminate\Database\Eloquent\Model;

class OrgInterface extends Model
{
    protected $table = 'orgchart_interfaces';

    protected $fillable = [
        'version_id', 'from_node_id', 'to_node_id', 'label', 'description',
    ];

    public function version()
    {
        return $this->belongsTo(OrgVersion::class, 'version_id');
    }

    public function fromNode()
    {
        return $this->belongsTo(OrgNode::class, 'from_node_id');
    }

    public function toNode()
    {
        return $this->belongsTo(OrgNode::class, 'to_node_id');
    }
}
