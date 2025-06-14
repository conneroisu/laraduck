import { r as n, j as e, S as A } from "./app-CRe4xN8M.js";
import {
  A as F,
  c as m,
  a as u,
  m as t,
  l as I,
  f as j,
  y as k,
  g as v,
  V as R,
  u as V,
  s as M,
  d as S,
  e as g,
  b as $,
  n as T,
  i as p,
  L as b,
  h as w,
} from "./Tracker-D7VM74TC.js";
import { F as _ } from "./ArrowTrendingUpIcon-Cf1rCHJL.js";
function E({ title: c, titleId: l, ...o }, x) {
  return n.createElement(
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
        ref: x,
        "aria-labelledby": l,
      },
      o,
    ),
    c ? n.createElement("title", { id: l }, c) : null,
    n.createElement("path", {
      strokeLinecap: "round",
      strokeLinejoin: "round",
      d: "M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z",
    }),
  );
}
const D = n.forwardRef(E);
function O({ title: c, titleId: l, ...o }, x) {
  return n.createElement(
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
        ref: x,
        "aria-labelledby": l,
      },
      o,
    ),
    c ? n.createElement("title", { id: l }, c) : null,
    n.createElement("path", {
      strokeLinecap: "round",
      strokeLinejoin: "round",
      d: "M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 1 1 0-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 0 1-1.44-4.282m3.102.069a18.03 18.03 0 0 1-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 0 1 8.835 2.535M10.34 6.66a23.847 23.847 0 0 0 8.835-2.535m0 0A23.74 23.74 0 0 0 18.795 3m.38 1.125a23.91 23.91 0 0 1 1.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 0 0 1.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 0 1 0 3.46",
    }),
  );
}
const P = n.forwardRef(O),
  H = ({
    rfmAnalysis: c,
    clvPrediction: l,
    churnAnalysis: o,
    geoDistribution: x,
    acquisitionChannels: N,
    cohortAnalysis: L,
  }) => {
    const [q, G] = n.useState(0),
      i = (s) =>
        new Intl.NumberFormat("en-US", {
          style: "currency",
          currency: "USD",
          minimumFractionDigits: 0,
          maximumFractionDigits: 0,
        }).format(s),
      h = (s) => new Intl.NumberFormat("en-US").format(s),
      y = o.map((s) => ({
        ...s,
        status: s.is_churned ? "Churned" : "Active",
        percentage:
          (s.customer_count / o.reduce((r, a) => r + a.customer_count, 0)) *
          100,
      })),
      f = {
        Champions: "emerald",
        "Loyal Customers": "indigo",
        "Potential Loyalists": "blue",
        "New Customers": "cyan",
        "At Risk": "amber",
        "Cant Lose Them": "orange",
        Lost: "red",
      },
      C = L.map((s) => {
        const r = { cohort: s.cohort };
        return (
          Object.entries(s.retention).forEach(([a, d]) => {
            r[`month_${a}`] = {
              customers: d.customers,
              revenue: d.revenue,
              percentage: s.retention[0]
                ? (d.customers / s.retention[0].customers) * 100
                : 0,
            };
          }),
          r
        );
      });
    return e.jsxs(F, {
      children: [
        e.jsx(A, { title: "Customer Analytics" }),
        e.jsx("div", {
          className: "py-12",
          children: e.jsxs("div", {
            className: "max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6",
            children: [
              e.jsx(m, {
                children: e.jsxs(u, {
                  justifyContent: "between",
                  alignItems: "center",
                  children: [
                    e.jsxs("div", {
                      children: [
                        e.jsx(t, {
                          className: "text-xl font-semibold",
                          children: "Customer Analytics",
                        }),
                        e.jsx(t, {
                          className: "text-sm text-gray-500 mt-1",
                          children:
                            "Deep insights into customer behavior and value",
                        }),
                      ],
                    }),
                    e.jsx(I, { className: "h-8 w-8 text-gray-400" }),
                  ],
                }),
              }),
              e.jsxs(m, {
                children: [
                  e.jsx(t, {
                    className: "text-lg font-semibold mb-4",
                    children: "RFM Segmentation Analysis",
                  }),
                  e.jsx(t, {
                    className: "text-sm text-gray-500 mb-6",
                    children:
                      "Customer segments based on Recency, Frequency, and Monetary value",
                  }),
                  e.jsxs(j, {
                    numItems: 1,
                    numItemsLg: 2,
                    className: "gap-6",
                    children: [
                      e.jsx(k, {
                        className: "h-72",
                        data: c,
                        category: "count",
                        index: "segment",
                        valueFormatter: h,
                        colors: Object.values(f),
                        showAnimation: !0,
                      }),
                      e.jsx("div", {
                        className: "space-y-4",
                        children: c.map((s) =>
                          e.jsx(
                            "div",
                            {
                              className: "p-4 border rounded-lg",
                              children: e.jsx(u, {
                                justifyContent: "between",
                                alignItems: "start",
                                children: e.jsxs("div", {
                                  className: "flex-1",
                                  children: [
                                    e.jsxs(u, {
                                      justifyContent: "start",
                                      alignItems: "center",
                                      className: "mb-2",
                                      children: [
                                        e.jsx(t, {
                                          className: "font-semibold",
                                          children: s.segment,
                                        }),
                                        e.jsxs(v, {
                                          color: f[s.segment],
                                          className: "ml-2",
                                          children: [s.count, " customers"],
                                        }),
                                      ],
                                    }),
                                    e.jsxs(j, {
                                      numItems: 3,
                                      className: "gap-2 text-sm",
                                      children: [
                                        e.jsxs("div", {
                                          children: [
                                            e.jsx(t, {
                                              className: "text-gray-500",
                                              children: "Avg Recency",
                                            }),
                                            e.jsxs(t, {
                                              className: "font-medium",
                                              children: [s.avgRecency, " days"],
                                            }),
                                          ],
                                        }),
                                        e.jsxs("div", {
                                          children: [
                                            e.jsx(t, {
                                              className: "text-gray-500",
                                              children: "Avg Frequency",
                                            }),
                                            e.jsxs(t, {
                                              className: "font-medium",
                                              children: [
                                                s.avgFrequency,
                                                " orders",
                                              ],
                                            }),
                                          ],
                                        }),
                                        e.jsxs("div", {
                                          children: [
                                            e.jsx(t, {
                                              className: "text-gray-500",
                                              children: "Avg Value",
                                            }),
                                            e.jsx(t, {
                                              className: "font-medium",
                                              children: i(s.avgMonetary),
                                            }),
                                          ],
                                        }),
                                      ],
                                    }),
                                  ],
                                }),
                              }),
                            },
                            s.segment,
                          ),
                        ),
                      }),
                    ],
                  }),
                ],
              }),
              e.jsxs(m, {
                children: [
                  e.jsxs(u, {
                    justifyContent: "between",
                    alignItems: "center",
                    className: "mb-4",
                    children: [
                      e.jsx(t, {
                        className: "text-lg font-semibold",
                        children: "Customer Lifetime Value Prediction",
                      }),
                      e.jsx(_, { className: "h-5 w-5 text-gray-400" }),
                    ],
                  }),
                  e.jsx(R, {
                    className: "h-80",
                    data: l.slice(0, 50),
                    x: "currentLtv",
                    y: "predictedValue",
                    size: "ordersPerMonth",
                    category: "segment",
                    colors: ["emerald", "indigo", "amber", "rose"],
                    valueFormatter: i,
                    showLegend: !0,
                    showAnimation: !0,
                  }),
                  e.jsxs("div", {
                    className: "mt-6",
                    children: [
                      e.jsx(t, {
                        className: "font-medium mb-4",
                        children: "Top 10 Highest Predicted CLV Customers",
                      }),
                      e.jsx("div", {
                        className: "overflow-x-auto",
                        children: e.jsxs("table", {
                          className: "min-w-full divide-y divide-gray-200",
                          children: [
                            e.jsx("thead", {
                              children: e.jsxs("tr", {
                                children: [
                                  e.jsx("th", {
                                    className:
                                      "px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider",
                                    children: "Customer",
                                  }),
                                  e.jsx("th", {
                                    className:
                                      "px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider",
                                    children: "Segment",
                                  }),
                                  e.jsx("th", {
                                    className:
                                      "px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider",
                                    children: "Current LTV",
                                  }),
                                  e.jsx("th", {
                                    className:
                                      "px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider",
                                    children: "Predicted 12m",
                                  }),
                                  e.jsx("th", {
                                    className:
                                      "px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider",
                                    children: "Growth",
                                  }),
                                ],
                              }),
                            }),
                            e.jsx("tbody", {
                              className: "bg-white divide-y divide-gray-200",
                              children: l.slice(0, 10).map((s, r) => {
                                const a =
                                  ((s.predictedValue - s.currentLtv) /
                                    s.currentLtv) *
                                  100;
                                return e.jsxs(
                                  "tr",
                                  {
                                    className:
                                      r % 2 === 0 ? "bg-white" : "bg-gray-50",
                                    children: [
                                      e.jsx("td", {
                                        className:
                                          "px-6 py-4 whitespace-nowrap",
                                        children: e.jsx(t, {
                                          className: "font-medium",
                                          children: s.name,
                                        }),
                                      }),
                                      e.jsx("td", {
                                        className:
                                          "px-6 py-4 whitespace-nowrap",
                                        children: e.jsx(v, {
                                          color:
                                            s.segment === "VIP"
                                              ? "emerald"
                                              : "gray",
                                          children: s.segment,
                                        }),
                                      }),
                                      e.jsx("td", {
                                        className:
                                          "px-6 py-4 whitespace-nowrap text-right",
                                        children: i(s.currentLtv),
                                      }),
                                      e.jsx("td", {
                                        className:
                                          "px-6 py-4 whitespace-nowrap text-right font-medium",
                                        children: i(s.predictedValue),
                                      }),
                                      e.jsx("td", {
                                        className:
                                          "px-6 py-4 whitespace-nowrap text-right",
                                        children: e.jsxs(V, {
                                          deltaType:
                                            a > 0 ? "increase" : "decrease",
                                          children: [a.toFixed(0), "%"],
                                        }),
                                      }),
                                    ],
                                  },
                                  s.customerId,
                                );
                              }),
                            }),
                          ],
                        }),
                      }),
                    ],
                  }),
                ],
              }),
              e.jsxs(M, {
                children: [
                  e.jsxs(S, {
                    children: [
                      e.jsx(g, { icon: $, children: "Churn Analysis" }),
                      e.jsx(g, {
                        icon: D,
                        children: "Geographic Distribution",
                      }),
                      e.jsx(g, { icon: P, children: "Acquisition Channels" }),
                    ],
                  }),
                  e.jsxs(T, {
                    children: [
                      e.jsx(p, {
                        children: e.jsxs(m, {
                          children: [
                            e.jsx(t, {
                              className: "text-lg font-semibold mb-4",
                              children: "Customer Churn Analysis",
                            }),
                            e.jsxs(j, {
                              numItems: 1,
                              numItemsLg: 2,
                              className: "gap-6",
                              children: [
                                e.jsx(b, {
                                  className: "h-72",
                                  data: y,
                                  index: "segment",
                                  categories: ["customer_count"],
                                  colors: ["rose", "emerald"],
                                  stack: !0,
                                  valueFormatter: h,
                                  showLegend: !0,
                                  showAnimation: !0,
                                }),
                                e.jsx("div", {
                                  className: "space-y-4",
                                  children: y.map((s) => {
                                    var r;
                                    return e.jsxs(
                                      "div",
                                      {
                                        className: "p-4 border rounded-lg",
                                        children: [
                                          e.jsxs(u, {
                                            justifyContent: "between",
                                            alignItems: "center",
                                            children: [
                                              e.jsxs("div", {
                                                children: [
                                                  e.jsx(t, {
                                                    className: "font-medium",
                                                    children: s.segment,
                                                  }),
                                                  e.jsx(t, {
                                                    className:
                                                      "text-sm text-gray-500",
                                                    children: s.status,
                                                  }),
                                                ],
                                              }),
                                              e.jsxs("div", {
                                                className: "text-right",
                                                children: [
                                                  e.jsx(t, {
                                                    className: "font-semibold",
                                                    children: h(
                                                      s.customer_count,
                                                    ),
                                                  }),
                                                  e.jsxs(t, {
                                                    className:
                                                      "text-sm text-gray-500",
                                                    children: [
                                                      "Risk Score: ",
                                                      (r = s.avg_risk_score) ==
                                                      null
                                                        ? void 0
                                                        : r.toFixed(2),
                                                    ],
                                                  }),
                                                ],
                                              }),
                                            ],
                                          }),
                                          e.jsx(w, {
                                            value: s.percentage,
                                            color: s.is_churned
                                              ? "rose"
                                              : "emerald",
                                            className: "mt-2",
                                          }),
                                        ],
                                      },
                                      `${s.status}-${s.segment}`,
                                    );
                                  }),
                                }),
                              ],
                            }),
                          ],
                        }),
                      }),
                      e.jsx(p, {
                        children: e.jsxs(m, {
                          children: [
                            e.jsx(t, {
                              className: "text-lg font-semibold mb-4",
                              children: "Geographic Distribution",
                            }),
                            e.jsx(b, {
                              className: "h-96",
                              data: x,
                              index: "country",
                              categories: ["customers", "totalLtv"],
                              colors: ["indigo", "emerald"],
                              valueFormatter: (s) => (s > 1e4 ? i(s) : h(s)),
                              showLegend: !0,
                              layout: "horizontal",
                              showAnimation: !0,
                            }),
                            e.jsx(j, {
                              numItems: 1,
                              numItemsSm: 2,
                              numItemsLg: 4,
                              className: "gap-4 mt-6",
                              children: x
                                .slice(0, 4)
                                .map((s) =>
                                  e.jsxs(
                                    m,
                                    {
                                      children: [
                                        e.jsx(t, {
                                          className: "text-sm text-gray-500",
                                          children: s.country,
                                        }),
                                        e.jsx(t, {
                                          className:
                                            "text-xl font-semibold mt-1",
                                          children: h(s.customers),
                                        }),
                                        e.jsxs(t, {
                                          className: "text-sm mt-2",
                                          children: ["Avg LTV: ", i(s.avgLtv)],
                                        }),
                                        e.jsxs(t, {
                                          className: "text-sm",
                                          children: [
                                            "Avg Orders: ",
                                            s.avgOrders,
                                          ],
                                        }),
                                      ],
                                    },
                                    s.country,
                                  ),
                                ),
                            }),
                          ],
                        }),
                      }),
                      e.jsx(p, {
                        children: e.jsxs(m, {
                          children: [
                            e.jsx(t, {
                              className: "text-lg font-semibold mb-4",
                              children: "Acquisition Channel Performance",
                            }),
                            e.jsx("div", {
                              className: "space-y-4",
                              children: N.map((s, r) => {
                                const a = Math.max(...N.map((d) => d.totalLtv));
                                return e.jsxs(
                                  "div",
                                  {
                                    className: "p-4 border rounded-lg",
                                    children: [
                                      e.jsx(u, {
                                        justifyContent: "between",
                                        alignItems: "start",
                                        children: e.jsxs("div", {
                                          className: "flex-1",
                                          children: [
                                            e.jsx(t, {
                                              className:
                                                "font-semibold text-lg",
                                              children: s.channel,
                                            }),
                                            e.jsxs(j, {
                                              numItems: 2,
                                              numItemsSm: 4,
                                              className: "gap-4 mt-2",
                                              children: [
                                                e.jsxs("div", {
                                                  children: [
                                                    e.jsx(t, {
                                                      className:
                                                        "text-sm text-gray-500",
                                                      children: "Customers",
                                                    }),
                                                    e.jsx(t, {
                                                      className: "font-medium",
                                                      children: h(s.customers),
                                                    }),
                                                  ],
                                                }),
                                                e.jsxs("div", {
                                                  children: [
                                                    e.jsx(t, {
                                                      className:
                                                        "text-sm text-gray-500",
                                                      children: "Avg LTV",
                                                    }),
                                                    e.jsx(t, {
                                                      className: "font-medium",
                                                      children: i(s.avgLtv),
                                                    }),
                                                  ],
                                                }),
                                                e.jsxs("div", {
                                                  children: [
                                                    e.jsx(t, {
                                                      className:
                                                        "text-sm text-gray-500",
                                                      children: "Total LTV",
                                                    }),
                                                    e.jsx(t, {
                                                      className: "font-medium",
                                                      children: i(s.totalLtv),
                                                    }),
                                                  ],
                                                }),
                                                e.jsxs("div", {
                                                  children: [
                                                    e.jsx(t, {
                                                      className:
                                                        "text-sm text-gray-500",
                                                      children: "Avg Lifespan",
                                                    }),
                                                    e.jsxs(t, {
                                                      className: "font-medium",
                                                      children: [
                                                        s.avgLifespan,
                                                        " days",
                                                      ],
                                                    }),
                                                  ],
                                                }),
                                              ],
                                            }),
                                          ],
                                        }),
                                      }),
                                      e.jsx(w, {
                                        value: (s.totalLtv / a) * 100,
                                        color: r === 0 ? "indigo" : "gray",
                                        className: "mt-4",
                                      }),
                                    ],
                                  },
                                  s.channel,
                                );
                              }),
                            }),
                          ],
                        }),
                      }),
                    ],
                  }),
                ],
              }),
              e.jsxs(m, {
                children: [
                  e.jsx(t, {
                    className: "text-lg font-semibold mb-4",
                    children: "Cohort Retention Analysis",
                  }),
                  e.jsx(t, {
                    className: "text-sm text-gray-500 mb-6",
                    children: "Customer retention by monthly cohorts",
                  }),
                  e.jsx("div", {
                    className: "overflow-x-auto",
                    children: e.jsxs("table", {
                      className: "min-w-full",
                      children: [
                        e.jsx("thead", {
                          children: e.jsxs("tr", {
                            children: [
                              e.jsx("th", {
                                className:
                                  "px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase",
                                children: "Cohort",
                              }),
                              [0, 1, 2, 3, 4, 5, 6].map((s) =>
                                e.jsxs(
                                  "th",
                                  {
                                    className:
                                      "px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase",
                                    children: ["Month ", s],
                                  },
                                  s,
                                ),
                              ),
                            ],
                          }),
                        }),
                        e.jsx("tbody", {
                          children: C.map((s) =>
                            e.jsxs(
                              "tr",
                              {
                                children: [
                                  e.jsx("td", {
                                    className: "px-4 py-2 font-medium text-sm",
                                    children: s.cohort,
                                  }),
                                  [0, 1, 2, 3, 4, 5, 6].map((r) => {
                                    const a = s[`month_${r}`];
                                    if (!a)
                                      return e.jsx(
                                        "td",
                                        { className: "px-4 py-2" },
                                        r,
                                      );
                                    const d =
                                      a.percentage > 80
                                        ? "bg-emerald-100"
                                        : a.percentage > 60
                                          ? "bg-green-100"
                                          : a.percentage > 40
                                            ? "bg-yellow-100"
                                            : a.percentage > 20
                                              ? "bg-orange-100"
                                              : "bg-red-100";
                                    return e.jsxs(
                                      "td",
                                      {
                                        className: `px-4 py-2 text-center ${d}`,
                                        children: [
                                          e.jsxs(t, {
                                            className: "font-medium text-sm",
                                            children: [
                                              a.percentage.toFixed(0),
                                              "%",
                                            ],
                                          }),
                                          e.jsx(t, {
                                            className: "text-xs text-gray-600",
                                            children: a.customers,
                                          }),
                                        ],
                                      },
                                      r,
                                    );
                                  }),
                                ],
                              },
                              s.cohort,
                            ),
                          ),
                        }),
                      ],
                    }),
                  }),
                ],
              }),
            ],
          }),
        }),
      ],
    });
  };
export { H as default };
