import { r as u, j as e, S as p } from "./app-CRe4xN8M.js";
import {
  A as b,
  c as x,
  a as n,
  m as s,
  F as f,
  b as y,
  s as v,
  d as E,
  e as R,
  n as _,
  i as T,
  f as N,
  g as d,
  h as C,
  j,
  k as t,
} from "./Tracker-D7VM74TC.js";
function A({ title: m, titleId: h, ...l }, g) {
  return u.createElement(
    "svg",
    Object.assign(
      {
        xmlns: "http://www.w3.org/2000/svg",
        fill: "none",
        viewBox: "0 0 24 24",
        strokeWidth: 1.5,
        stroke: "currentColor",
        "aria-hidden": "true",
        "data-slot": "icon",
        ref: g,
        "aria-labelledby": h,
      },
      l,
    ),
    m ? u.createElement("title", { id: h }, m) : null,
    u.createElement("path", {
      strokeLinecap: "round",
      strokeLinejoin: "round",
      d: "M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 0 0-16.536-1.84M7.5 14.25 5.106 5.272M6 20.25a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm12.75 0a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Z",
    }),
  );
}
const I = u.forwardRef(A),
  O = ({ salesRanking: m, crossSellAnalysis: h }) => {
    const l = (a) =>
        new Intl.NumberFormat("en-US", {
          style: "currency",
          currency: "USD",
          minimumFractionDigits: 0,
          maximumFractionDigits: 0,
        }).format(a),
      g = { North: "slate", South: "violet", East: "indigo", West: "rose" };
    return e.jsxs(b, {
      children: [
        e.jsx(p, { title: "Advanced Analytics" }),
        e.jsx("div", {
          className: "py-12",
          children: e.jsxs("div", {
            className: "max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6",
            children: [
              e.jsx(x, {
                children: e.jsxs(n, {
                  justifyContent: "between",
                  alignItems: "center",
                  children: [
                    e.jsxs("div", {
                      children: [
                        e.jsx(s, {
                          className: "text-xl font-semibold",
                          children: "Advanced Analytics",
                        }),
                        e.jsx(s, {
                          className: "text-sm text-gray-500 mt-1",
                          children:
                            "Window functions, cross-sell analysis, and advanced SQL features",
                        }),
                      ],
                    }),
                    e.jsx(f, { className: "h-8 w-8 text-gray-400" }),
                  ],
                }),
              }),
              e.jsxs(x, {
                children: [
                  e.jsxs(n, {
                    justifyContent: "between",
                    alignItems: "center",
                    className: "mb-6",
                    children: [
                      e.jsxs("div", {
                        children: [
                          e.jsx(s, {
                            className: "text-lg font-semibold",
                            children: "Regional Sales Ranking",
                          }),
                          e.jsx(s, {
                            className: "text-sm text-gray-500 mt-1",
                            children:
                              "Demonstrating window functions with RANK() and PERCENT_RANK()",
                          }),
                        ],
                      }),
                      e.jsx(y, { className: "h-5 w-5 text-gray-400" }),
                    ],
                  }),
                  e.jsxs(v, {
                    children: [
                      e.jsx(E, {
                        children: Object.keys(m).map((a) =>
                          e.jsx(R, { children: a }, a),
                        ),
                      }),
                      e.jsx(_, {
                        children: Object.entries(m).map(([a, i]) => {
                          var c, o;
                          return e.jsx(
                            T,
                            {
                              children: e.jsxs("div", {
                                className: "space-y-4",
                                children: [
                                  e.jsx("div", {
                                    className: "p-4 bg-gray-50 rounded-lg mb-6",
                                    children: e.jsxs(N, {
                                      numItems: 1,
                                      numItemsSm: 3,
                                      className: "gap-4",
                                      children: [
                                        e.jsxs("div", {
                                          children: [
                                            e.jsx(s, {
                                              className:
                                                "text-sm text-gray-500",
                                              children: "Region Total",
                                            }),
                                            e.jsx(s, {
                                              className:
                                                "text-xl font-semibold",
                                              children: l(
                                                ((c = i[0]) == null
                                                  ? void 0
                                                  : c.regionTotal) || 0,
                                              ),
                                            }),
                                          ],
                                        }),
                                        e.jsxs("div", {
                                          children: [
                                            e.jsx(s, {
                                              className:
                                                "text-sm text-gray-500",
                                              children: "Average Order",
                                            }),
                                            e.jsx(s, {
                                              className:
                                                "text-xl font-semibold",
                                              children: l(
                                                ((o = i[0]) == null
                                                  ? void 0
                                                  : o.regionAvg) || 0,
                                              ),
                                            }),
                                          ],
                                        }),
                                        e.jsxs("div", {
                                          children: [
                                            e.jsx(s, {
                                              className:
                                                "text-sm text-gray-500",
                                              children: "Orders in Top 10",
                                            }),
                                            e.jsx(s, {
                                              className:
                                                "text-xl font-semibold",
                                              children: i.length,
                                            }),
                                          ],
                                        }),
                                      ],
                                    }),
                                  }),
                                  i.map((r) =>
                                    e.jsxs(
                                      "div",
                                      {
                                        className:
                                          "p-4 border rounded-lg hover:bg-gray-50 transition-colors",
                                        children: [
                                          e.jsxs(n, {
                                            justifyContent: "between",
                                            alignItems: "start",
                                            children: [
                                              e.jsxs("div", {
                                                className:
                                                  "flex items-start space-x-4",
                                                children: [
                                                  e.jsxs("div", {
                                                    className:
                                                      "flex flex-col items-center",
                                                    children: [
                                                      e.jsxs(d, {
                                                        color:
                                                          r.rank === 1
                                                            ? "amber"
                                                            : r.rank <= 3
                                                              ? "gray"
                                                              : "slate",
                                                        size: "lg",
                                                        children: ["#", r.rank],
                                                      }),
                                                      e.jsx(s, {
                                                        className:
                                                          "text-xs text-gray-500 mt-1",
                                                        children: "Rank",
                                                      }),
                                                    ],
                                                  }),
                                                  e.jsxs("div", {
                                                    children: [
                                                      e.jsxs(s, {
                                                        className:
                                                          "font-semibold",
                                                        children: [
                                                          "Order #",
                                                          r.orderId,
                                                        ],
                                                      }),
                                                      e.jsx(s, {
                                                        className:
                                                          "text-2xl font-bold mt-1",
                                                        children: l(r.amount),
                                                      }),
                                                      e.jsxs(n, {
                                                        className:
                                                          "mt-2 space-x-4",
                                                        children: [
                                                          e.jsxs(d, {
                                                            color: g[a],
                                                            children: [
                                                              a,
                                                              " Region",
                                                            ],
                                                          }),
                                                          e.jsxs(s, {
                                                            className:
                                                              "text-sm text-gray-500",
                                                            children: [
                                                              "Top ",
                                                              r.percentile,
                                                              "%",
                                                            ],
                                                          }),
                                                        ],
                                                      }),
                                                    ],
                                                  }),
                                                ],
                                              }),
                                              e.jsxs("div", {
                                                className: "text-right",
                                                children: [
                                                  e.jsx(s, {
                                                    className:
                                                      "text-sm text-gray-500",
                                                    children: "% of Region Avg",
                                                  }),
                                                  e.jsxs(s, {
                                                    className:
                                                      "text-lg font-semibold",
                                                    children: [
                                                      (
                                                        (r.amount /
                                                          r.regionAvg) *
                                                        100
                                                      ).toFixed(0),
                                                      "%",
                                                    ],
                                                  }),
                                                ],
                                              }),
                                            ],
                                          }),
                                          e.jsxs("div", {
                                            className: "mt-3",
                                            children: [
                                              e.jsxs(n, {
                                                justifyContent: "between",
                                                className: "mb-1",
                                                children: [
                                                  e.jsx(s, {
                                                    className:
                                                      "text-xs text-gray-500",
                                                    children:
                                                      "Relative to region average",
                                                  }),
                                                  e.jsxs(s, {
                                                    className:
                                                      "text-xs font-medium",
                                                    children: [
                                                      r.amount > r.regionAvg
                                                        ? "+"
                                                        : "",
                                                      l(r.amount - r.regionAvg),
                                                    ],
                                                  }),
                                                ],
                                              }),
                                              e.jsx(C, {
                                                value: Math.min(
                                                  (r.amount / r.regionAvg) *
                                                    100,
                                                  150,
                                                ),
                                                color:
                                                  r.amount > r.regionAvg
                                                    ? "emerald"
                                                    : "amber",
                                              }),
                                            ],
                                          }),
                                        ],
                                      },
                                      r.orderId,
                                    ),
                                  ),
                                ],
                              }),
                            },
                            a,
                          );
                        }),
                      }),
                    ],
                  }),
                  e.jsxs("div", {
                    className: "mt-6 p-4 bg-gray-900 rounded-lg",
                    children: [
                      e.jsx(s, {
                        className: "text-xs text-gray-400 mb-2",
                        children: "SQL Example using Window Functions:",
                      }),
                      e.jsx("pre", {
                        className: "text-xs text-gray-300 overflow-x-auto",
                        children: `WITH ranked_sales AS (
    SELECT 
        order_id,
        region,
        total_amount,
        RANK() OVER (PARTITION BY region ORDER BY total_amount DESC) as rank_in_region,
        PERCENT_RANK() OVER (PARTITION BY region ORDER BY total_amount DESC) as percentile,
        SUM(total_amount) OVER (PARTITION BY region) as region_total,
        AVG(total_amount) OVER (PARTITION BY region) as region_avg
    FROM sales
    WHERE sale_date >= CURRENT_DATE - INTERVAL '7 days'
)
SELECT * FROM ranked_sales 
WHERE rank_in_region <= 10
ORDER BY region, rank_in_region;`,
                      }),
                    ],
                  }),
                ],
              }),
              e.jsxs(x, {
                children: [
                  e.jsxs(n, {
                    justifyContent: "between",
                    alignItems: "center",
                    className: "mb-6",
                    children: [
                      e.jsxs("div", {
                        children: [
                          e.jsx(s, {
                            className: "text-lg font-semibold",
                            children: "Cross-Sell Recommendations",
                          }),
                          e.jsx(s, {
                            className: "text-sm text-gray-500 mt-1",
                            children:
                              "Products frequently bought together using CTEs and complex joins",
                          }),
                        ],
                      }),
                      e.jsx(I, { className: "h-5 w-5 text-gray-400" }),
                    ],
                  }),
                  e.jsx(N, {
                    numItems: 1,
                    numItemsLg: 2,
                    className: "gap-6",
                    children: Object.entries(h).map(([a, i]) =>
                      e.jsxs(
                        x,
                        {
                          children: [
                            e.jsxs("div", {
                              className: "mb-4",
                              children: [
                                e.jsx(s, {
                                  className: "font-semibold text-lg",
                                  children: i.product,
                                }),
                                e.jsxs(s, {
                                  className: "text-sm text-gray-500",
                                  children: ["Product #", a],
                                }),
                              ],
                            }),
                            e.jsx(s, {
                              className: "text-sm font-medium mb-3",
                              children: "Frequently Bought Together:",
                            }),
                            e.jsx("div", {
                              className: "space-y-3",
                              children: i.recommendations.map((c, o) =>
                                e.jsxs(
                                  "div",
                                  {
                                    className:
                                      "flex items-center justify-between p-3 bg-gray-50 rounded-lg",
                                    children: [
                                      e.jsxs("div", {
                                        className:
                                          "flex items-center space-x-3",
                                        children: [
                                          e.jsxs(d, {
                                            color: o === 0 ? "emerald" : "gray",
                                            size: "sm",
                                            children: ["#", o + 1],
                                          }),
                                          e.jsxs("div", {
                                            children: [
                                              e.jsx(s, {
                                                className: "font-medium",
                                                children: c.name,
                                              }),
                                              e.jsxs(s, {
                                                className:
                                                  "text-xs text-gray-500",
                                                children: [
                                                  c.coOccurrence,
                                                  " co-purchases",
                                                ],
                                              }),
                                            ],
                                          }),
                                        ],
                                      }),
                                      e.jsxs("div", {
                                        className: "text-right",
                                        children: [
                                          e.jsx(s, {
                                            className: "font-semibold",
                                            children: l(c.revenue),
                                          }),
                                          e.jsx(s, {
                                            className: "text-xs text-gray-500",
                                            children: "revenue",
                                          }),
                                        ],
                                      }),
                                    ],
                                  },
                                  c.productId,
                                ),
                              ),
                            }),
                            e.jsxs("div", {
                              className: "mt-4 p-3 bg-blue-50 rounded-lg",
                              children: [
                                e.jsx(s, {
                                  className:
                                    "text-xs font-medium text-blue-900",
                                  children: "💡 Recommendation:",
                                }),
                                e.jsxs(s, {
                                  className: "text-xs text-blue-800 mt-1",
                                  children: [
                                    "Bundle these products for a ",
                                    Math.floor(Math.random() * 5 + 10),
                                    "% discount to increase basket size",
                                  ],
                                }),
                              ],
                            }),
                          ],
                        },
                        a,
                      ),
                    ),
                  }),
                  e.jsxs("div", {
                    className: "mt-6 p-4 bg-gray-900 rounded-lg",
                    children: [
                      e.jsx(s, {
                        className: "text-xs text-gray-400 mb-2",
                        children:
                          "SQL Example using CTEs for Cross-sell Analysis:",
                      }),
                      e.jsx("pre", {
                        className: "text-xs text-gray-300 overflow-x-auto",
                        children: `WITH orders_with_product AS (
    SELECT DISTINCT order_id 
    FROM sales 
    WHERE product_id = :target_product_id
),
cross_sell_products AS (
    SELECT 
        p.product_id,
        p.name,
        p.category,
        COUNT(DISTINCT s.order_id) as co_occurrence_count,
        SUM(s.total_amount) as total_revenue
    FROM products p
    JOIN sales s ON p.product_id = s.product_id
    JOIN orders_with_product owp ON s.order_id = owp.order_id
    WHERE p.product_id != :target_product_id
    GROUP BY p.product_id, p.name, p.category
)
SELECT * FROM cross_sell_products
ORDER BY co_occurrence_count DESC
LIMIT 5;`,
                      }),
                    ],
                  }),
                ],
              }),
              e.jsxs(x, {
                children: [
                  e.jsx(s, {
                    className: "text-lg font-semibold mb-4",
                    children: "Laraduck Advanced Features Showcase",
                  }),
                  e.jsxs(N, {
                    numItems: 1,
                    numItemsSm: 2,
                    className: "gap-4",
                    children: [
                      e.jsxs("div", {
                        className: "p-4 border rounded-lg",
                        children: [
                          e.jsx(n, {
                            justifyContent: "start",
                            alignItems: "center",
                            className: "mb-2",
                            children: e.jsx(d, {
                              color: "indigo",
                              children: "Window Functions",
                            }),
                          }),
                          e.jsx(s, {
                            className: "text-sm font-medium",
                            children: "Supported Functions:",
                          }),
                          e.jsxs(j, {
                            className: "mt-2",
                            children: [
                              e.jsx(t, {
                                children: e.jsx(s, {
                                  className: "text-sm",
                                  children:
                                    "ROW_NUMBER() - Sequential numbering",
                                }),
                              }),
                              e.jsx(t, {
                                children: e.jsx(s, {
                                  className: "text-sm",
                                  children: "RANK() - Ranking with gaps",
                                }),
                              }),
                              e.jsx(t, {
                                children: e.jsx(s, {
                                  className: "text-sm",
                                  children:
                                    "DENSE_RANK() - Ranking without gaps",
                                }),
                              }),
                              e.jsx(t, {
                                children: e.jsx(s, {
                                  className: "text-sm",
                                  children:
                                    "PERCENT_RANK() - Percentile ranking",
                                }),
                              }),
                              e.jsx(t, {
                                children: e.jsx(s, {
                                  className: "text-sm",
                                  children:
                                    "LAG/LEAD - Access previous/next rows",
                                }),
                              }),
                            ],
                          }),
                        ],
                      }),
                      e.jsxs("div", {
                        className: "p-4 border rounded-lg",
                        children: [
                          e.jsx(n, {
                            justifyContent: "start",
                            alignItems: "center",
                            className: "mb-2",
                            children: e.jsx(d, {
                              color: "emerald",
                              children: "CTEs & Subqueries",
                            }),
                          }),
                          e.jsx(s, {
                            className: "text-sm font-medium",
                            children: "Common Table Expressions:",
                          }),
                          e.jsxs(j, {
                            className: "mt-2",
                            children: [
                              e.jsx(t, {
                                children: e.jsx(s, {
                                  className: "text-sm",
                                  children: "Recursive CTEs for hierarchies",
                                }),
                              }),
                              e.jsx(t, {
                                children: e.jsx(s, {
                                  className: "text-sm",
                                  children: "Multiple CTEs in single query",
                                }),
                              }),
                              e.jsx(t, {
                                children: e.jsx(s, {
                                  className: "text-sm",
                                  children: "Materialized CTEs for performance",
                                }),
                              }),
                              e.jsx(t, {
                                children: e.jsx(s, {
                                  className: "text-sm",
                                  children: "Complex nested subqueries",
                                }),
                              }),
                              e.jsx(t, {
                                children: e.jsx(s, {
                                  className: "text-sm",
                                  children: "Correlated subqueries",
                                }),
                              }),
                            ],
                          }),
                        ],
                      }),
                      e.jsxs("div", {
                        className: "p-4 border rounded-lg",
                        children: [
                          e.jsx(n, {
                            justifyContent: "start",
                            alignItems: "center",
                            className: "mb-2",
                            children: e.jsx(d, {
                              color: "cyan",
                              children: "Analytical Functions",
                            }),
                          }),
                          e.jsx(s, {
                            className: "text-sm font-medium",
                            children: "Built-in Analytics:",
                          }),
                          e.jsxs(j, {
                            className: "mt-2",
                            children: [
                              e.jsx(t, {
                                children: e.jsx(s, {
                                  className: "text-sm",
                                  children: "PIVOT/UNPIVOT operations",
                                }),
                              }),
                              e.jsx(t, {
                                children: e.jsx(s, {
                                  className: "text-sm",
                                  children: "Time series analysis",
                                }),
                              }),
                              e.jsx(t, {
                                children: e.jsx(s, {
                                  className: "text-sm",
                                  children: "Statistical aggregations",
                                }),
                              }),
                              e.jsx(t, {
                                children: e.jsx(s, {
                                  className: "text-sm",
                                  children: "Moving averages",
                                }),
                              }),
                              e.jsx(t, {
                                children: e.jsx(s, {
                                  className: "text-sm",
                                  children: "Cumulative calculations",
                                }),
                              }),
                            ],
                          }),
                        ],
                      }),
                      e.jsxs("div", {
                        className: "p-4 border rounded-lg",
                        children: [
                          e.jsx(n, {
                            justifyContent: "start",
                            alignItems: "center",
                            className: "mb-2",
                            children: e.jsx(d, {
                              color: "amber",
                              children: "File Operations",
                            }),
                          }),
                          e.jsx(s, {
                            className: "text-sm font-medium",
                            children: "Data Import/Export:",
                          }),
                          e.jsxs(j, {
                            className: "mt-2",
                            children: [
                              e.jsx(t, {
                                children: e.jsx(s, {
                                  className: "text-sm",
                                  children: "Parquet file support",
                                }),
                              }),
                              e.jsx(t, {
                                children: e.jsx(s, {
                                  className: "text-sm",
                                  children: "CSV import/export",
                                }),
                              }),
                              e.jsx(t, {
                                children: e.jsx(s, {
                                  className: "text-sm",
                                  children: "JSON data handling",
                                }),
                              }),
                              e.jsx(t, {
                                children: e.jsx(s, {
                                  className: "text-sm",
                                  children: "Direct file querying",
                                }),
                              }),
                              e.jsx(t, {
                                children: e.jsx(s, {
                                  className: "text-sm",
                                  children: "S3 integration ready",
                                }),
                              }),
                            ],
                          }),
                        ],
                      }),
                    ],
                  }),
                ],
              }),
            ],
          }),
        }),
      ],
    });
  };
export { O as default };
