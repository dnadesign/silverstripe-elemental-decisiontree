<div class="decisiontree" role="region" aria-label="Decision tree">
	<div class="decisiontree-header">
		<% if not $HideTitle %><div class="decisiontree-title">$Title</div><% end_if %>
		<% if $Introduction %><div class="decisiontree-intro">$Introduction</div><% end_if %>
	</div>

	<div class="decisiontree-main">
		<% include DNADesign\SilverStripeElementalDecisionTree\Model\DecisionTreeStep Step=$FirstStep, Controller=$Controller %>
	</div>

	<div class="decisiontree-announcer sr-only" aria-live="polite" aria-atomic="true"></div>
</div>
