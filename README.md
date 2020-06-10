# Proj CA FACTS

Reference Docs:
- https://docs.google.com/document/d/1_zbKm0-n_3Z3Vw5jXZMriEdVvWJ2h-clKJl6sWcnE0I/edit
-

Projects:
CA FACTS ACCESS CODES
1. Make a project to house the Access Codes (e.g. one per household)
1. Obtain or generate a list of access codes (numerical for phone entry)



CA FACTS KIT ORDER (MAIN) (19070)
1. Arrive at EM page with CODE, get ZIP, verify.
1. Twilio IVR endpoint to also register
Q: Are we going to get person codes for the box shippedfor household code)
Q: Is household code synonomyous with kit-id (or box-id) that is shipped?


CA FACTS KIT SUBMISSION (19187)
1. Upon SUBMIT (Complete) of the one-form survey, we want to:
   - Use Household Code (household_id) to identify record in MAIN project
   - link results to CA FACTS KIT ORDER (MAIN) project.
2. Determine if the survey was HH or DEP, and fill fields from Kit Order Linkage form in MAIN project
   - Q: Do we know if a kit is DEP2 or DEP3 or can we just use the next one available...
   - Fill in information
   - BONUS: If we have time, we can copy the results from the Kit Submission to the MAIN project for english-only results


TODO:  Check with Gauss to see if they are making the ACCESS codes or we should make them.

