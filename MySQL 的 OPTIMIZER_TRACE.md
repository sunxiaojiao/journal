# MySQL 的 OPTIMIZER_TRACE

MySQL的的查询过程大体为：外层服务->查询缓存->解析器&预处理器->查询优化器->存储引擎。查询优化器是一个关键的组件，直接决定如何调用存储引擎查询数据，决定索引的使用。

MySQL的查询优化器基于代价CBO(Cost-Based Optimization)，即它会计算所有可能的执行路径及其代价，选择代价最小的一条路径执行。

了解查询优化器的工作过程无疑对设计高效的数据库和查询大有裨益。explain有时候让我们云里雾里的，MySQL5.6推出了optimizer_trace，方便我们去分析查询优化器的过程。现在我们用它做一次分析。

## 获取trace
```mysql
# 建议在会话级别开启，影响最小
SET optimizer_trace="enabled=on";

# 随着优化过程，trace会被不断append，当超过这个设定值，会停止append，并将未append的数据大小以bytes为单位写入字段MISSING_BYTES_BEYOND_MAX_MEM_SIZE
SET optimizer_trace_max_mem_size=102400;

# 执行你的sql
SELECT ...;

# 查看trace
SELECT * FROM INFORMATION_SCHEMA.OPTIMIZER_TRACE;
# 导出trace
SELECT TRACE INTO DUMPFILE <filename> FROM  INFORMATION_SCHEMA.OPTIMIZER_TRACE;

# 关闭trace
SET optimizer_trace="enabled=off";
```
***Note: 不要使用navicat等工具，因为这些工具会执行额外的sql。建议使用mysql命令行客户端。***

## 分析trace
以一个联合索引的例子，通过optimizer_trace查看优化器如何处理和选择合适的索引
### 创建测试数据表

```mysql
CREATE TABLE `test`  (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `a` varchar(30) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `b` int(11) NOT NULL,
  `c` varchar(100) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT '',
  `d` datetime(0) NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `idx_a_b_d`(`a`, `b`, `d`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8 COLLATE = utf8_general_ci ROW_FORMAT = Compact;
```
插入一些数据
```mysql
insert into test (`a`, `b`, `c`, `d`) values ('aadf', 14123, '',  '2018-09-01 10:00:00');
```

### 执行查询并获取trace
```mysql
# 只列出查询sql，获取trace的过程省略
select * from `test` where `a` = 'aadf' and b in(1,2,14123) and d > '2018-01-01 00:00:00'
```
### 分析trace
先看一下explain的结果
```
+----+-------------+-------+------+---------------+------+---------+------+------+-------------+
| id | select_type | table | type | possible_keys | key  | key_len | ref  | rows | Extra       |
+----+-------------+-------+------+---------------+------+---------+------+------+-------------+
|  1 | SIMPLE      | test  | ALL  | idx_a_b_d     | NULL | NULL    | NULL |    1 | Using where |
+----+-------------+-------+------+---------------+------+---------+------+------+-------------+
```



查询可以用的索引有idx_a_b_d，但是最后选择了全表扫描



现在看一下optimizer_trace

```json
{
  "steps": [
    {
      "join_preparation": {
        "select#": 1,
        "steps": [
          {
            "expanded_query": "/* select#1 */ select `test`.`id` AS `id`,`test`.`a` AS `a`,`test`.`b` AS `b`,`test`.`c` AS `c`,`test`.`d` AS `d` from `test` where ((`test`.`a` = 'aadf') and (`test`.`b` in (1,2,14123)) and (`test`.`d` > '2018-01-01 00:00:00'))"
          }
        ]
      }
    },
    {
      "join_optimization": {
        "select#": 1,
        "steps": [
          {
            "condition_processing": {
              "condition": "WHERE",
              "original_condition": "((`test`.`a` = 'aadf') and (`test`.`b` in (1,2,14123)) and (`test`.`d` > '2018-01-01 00:00:00'))",
              "steps": [
                {
                  "transformation": "equality_propagation",
                  "resulting_condition": "((`test`.`a` = 'aadf') and (`test`.`b` in (1,2,14123)) and (`test`.`d` > '2018-01-01 00:00:00'))"
                },
                {
                  "transformation": "constant_propagation",
                  "resulting_condition": "((`test`.`a` = 'aadf') and (`test`.`b` in (1,2,14123)) and (`test`.`d` > '2018-01-01 00:00:00'))"
                },
                {
                  "transformation": "trivial_condition_removal",
                  "resulting_condition": "((`test`.`a` = 'aadf') and (`test`.`b` in (1,2,14123)) and (`test`.`d` > '2018-01-01 00:00:00'))"
                }
              ]
            }
          },
          {
            "table_dependencies": [
              {
                "table": "`test`",
                "row_may_be_null": false,
                "map_bit": 0,
                "depends_on_map_bits": [
                ]
              }
            ]
          },
          {
            "ref_optimizer_key_uses": [
              {
                "table": "`test`",
                "field": "a",
                "equals": "'aadf'",
                "null_rejecting": false
              }
            ]
          },
          {
            "rows_estimation": [
              {
                "table": "`test`",
                "range_analysis": {
                  "table_scan": {
                    "rows": 1,
                    "cost": 3.3
                  },
                  "potential_range_indices": [
                    {
                      "index": "PRIMARY",
                      "usable": false,
                      "cause": "not_applicable"
                    },
                    {
                      "index": "idx_a_b_d",
                      "usable": true,
                      "key_parts": [
                        "a",
                        "b",
                        "d",
                        "id"
                      ]
                    }
                  ],
                  "setup_range_conditions": [
                  ],
                  "group_index_range": {
                    "chosen": false,
                    "cause": "not_group_by_or_distinct"
                  },
                  "analyzing_range_alternatives": {
                    "range_scan_alternatives": [
                      {
                        "index": "idx_a_b_d",
                        "ranges": [
                          "aadf <= a <= aadf AND 1 <= b <= 1 AND 2018-01-01 00:00:00 < d",
                          "aadf <= a <= aadf AND 2 <= b <= 2 AND 2018-01-01 00:00:00 < d",
                          "aadf <= a <= aadf AND 14123 <= b <= 14123 AND 2018-01-01 00:00:00 < d"
                        ],
                        "index_dives_for_eq_ranges": true,
                        "rowid_ordered": false,
                        "using_mrr": false,
                        "index_only": false,
                        "rows": 3,
                        "cost": 6.61,
                        "chosen": false,
                        "cause": "cost"
                      }
                    ],
                    "analyzing_roworder_intersect": {
                      "usable": false,
                      "cause": "too_few_roworder_scans"
                    }
                  }
                }
              }
            ]
          },
          {
            "considered_execution_plans": [
              {
                "plan_prefix": [
                ],
                "table": "`test`",
                "best_access_path": {
                  "considered_access_paths": [
                    {
                      "access_type": "ref",
                      "index": "idx_a_b_d",
                      "rows": 3,
                      "cost": 2.6,
                      "chosen": true
                    },
                    {
                      "access_type": "scan",
                      "rows": 1,
                      "cost": 1.2,
                      "chosen": true
                    }
                  ]
                },
                "cost_for_plan": 1.2,
                "rows_for_plan": 1,
                "chosen": true
              }
            ]
          },
          {
            "attaching_conditions_to_tables": {
              "original_condition": "((`test`.`a` = 'aadf') and (`test`.`b` in (1,2,14123)) and (`test`.`d` > '2018-01-01 00:00:00'))",
              "attached_conditions_computation": [
              ],
              "attached_conditions_summary": [
                {
                  "table": "`test`",
                  "attached": "((`test`.`a` = 'aadf') and (`test`.`b` in (1,2,14123)) and (`test`.`d` > '2018-01-01 00:00:00'))"
                }
              ]
            }
          },
          {
            "refine_plan": [
              {
                "table": "`test`",
                "access_type": "table_scan"
              }
            ]
          }
        ]
      }
    },
    {
      "join_execution": {
        "select#": 1,
        "steps": [
        ]
      }
    }
  ]
}
```
查询优化器分析得出两条执行路径，一个是全表扫描。一个是走idx_a_b_d索引，进一步计算得到全表扫描的代价为3.3，走idx_a_b_d索引的代价为6.61。这个结果很正常，可以看到如果选择走索引，需要在b-tree上走三条路径才能把结果行跑出来，因为现在数据就一条，自然走全表的代价更小，随着数据的增加，走索引的代价会比全表的代价越来越小，到时候自然会选择走索引。