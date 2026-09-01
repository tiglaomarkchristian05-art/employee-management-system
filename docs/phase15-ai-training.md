# Phase 15 — AI-Based Training Recommendation

## Method

The system uses an internal, deterministic **content-based recommendation and weighted similarity** method (`content_similarity_v1`). Supervised machine learning was not selected because the current local dataset has only a small number of training outcomes and no reliable recommendation-acceptance labels. No external API receives employee data.

## Data used

Only job/training data is analyzed: employee position and department; skills-matrix proficiency gaps; course title, description, category, required skills, target position/department and prerequisite; participation status; completion date; attendance; assessment/quiz result; and recurrence configuration. Address, contact information, salary and government identifiers are excluded.

Historical outcomes come from `training_registrations`. Admin/HR can encode an existing employee/course outcome on Training Needs Analysis. Labels are normalized by lowercase tokenization, punctuation removal, stop-word removal and consistent department/position IDs. Missing skill/history values safely contribute zero rather than being fabricated. Unique database keys prevent duplicate employee/course recommendations.

## Score and explanation

The score is reproducible and ranges from 0–100:

- Skill-gap cosine similarity: 35 points
- Position relevance: 25 points
- Department relevance: 15 points
- Missing/due training history: 15 points
- Weak or failed related-category assessment: 10 points

Explicit target IDs use exact normalized database matches. Untargeted courses use cosine similarity between normalized employee role/department terms and course metadata. Recommendations below 25 are not stored. Priority is High at 70+, Medium at 45–69.99 and Low at 25–44.99. Every stored row includes its factor breakdown, detected gap, reason and algorithm version.

Before scoring, the engine excludes cancelled/inactive/expired/full training, active duplicate participation, completed training not due for recurrence, and training whose mandatory prerequisite is incomplete. HR must accept, dismiss or explicitly assign. Assignment calls the existing `Training::assignEmployees()` workflow.

## Evaluation

This content-based version does not claim fabricated accuracy. Evaluation uses reproducible scenario and HR relevance testing: relevant role/skill gaps should rank higher; completed non-recurring training must be excluded; irrelevant profiles should produce low/no results; weak related assessments should raise the need score; and unmet prerequisites must exclude immediate recommendation. Future agency history can support top-K acceptance/relevance evaluation and, once enough labeled outcomes exist, comparison with an explainable nearest-neighbor or ranking model.

## Defense demo

1. Admin configures a training’s target department/position, skills and prerequisite.
2. Admin opens **Training Needs Analysis**, selects an employee and runs analysis.
3. The system loads only job/training features, preprocesses labels, filters invalid candidates, calculates factor scores and persists the explanation.
4. Admin opens **Why?**, reviews the score, accepts and assigns it.
5. Existing assignment creates the participation record, notification and audit entry.
6. Employee opens **Recommended for You** or **My Trainings** and sees only their own recommendation/assignment.

## Limitations and future improvement

Recommendation quality depends on consistent skills, targets and historical outcomes. Sparse metadata can correctly result in no recommendation. The current engine does not infer skills from private documents, use government identifiers, or claim predictive accuracy. More client history—especially HR decisions, completion outcomes and post-training competency improvement—can support validated weight tuning and future supervised ranking without changing the human approval rule.