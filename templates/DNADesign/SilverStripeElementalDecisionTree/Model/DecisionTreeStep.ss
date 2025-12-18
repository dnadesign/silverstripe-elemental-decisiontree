<% if $Step.Type == 'Question' %>
<div class="step<% if $FirstStep.ID == $Step.ID %> step--first<% end_if %>" data-step-id="$Step.ID">
	<form action="$Controller.Link('getNextStepForAnswer')" method="post" class="step-form">
		<% with $Step %>
		<fieldset>
			<legend class="step-legend <% if $Content %>step-legend--withcontent<% end_if %>" id="step-legend-{$ID}">
				<span class="step-title">
					<span class="step-number">{$PositionInPathway}.</span>
					<span class="step-title-inner">$Title</span>
				</span>
				<% if $Content %><span class="step-content">$Content</span><% end_if %>
			</legend>
			<% if Answers %>
				<ul class="optionset step-options">
				<% loop $Answers %>
					<li>
						<input id="answer-$ID" class="radio step-option" name="stepanswerid" type="radio" value="$ID" aria-describedby="step-legend-{$Up.ID}"<% if $Top.Controller.getIsAnswerSelected($ID) %> checked<% end_if %> />
						<label for="answer-$ID">$Title</label>
					</li>
				<% end_loop %>
				</ul>
			<% end_if %>
		</fieldset>
		<% end_with %>
	</form>

	<div class="nextstep">
		<% if $Controller.getNextStepFromSelectedAnswer($Step.ID) %>
			<% include DecisionTreeStep Step=$Controller.getNextStepFromSelectedAnswer($Step.ID), Controller=$Controller %>
		<% end_if %>
	</div>
</div>
<% else %>
<div class="step step--result" data-step-id="$Step.ID" data-step-type="result">
	<% with $Step %>
		<% if $Title && not $HideTitle %><div class="step-title" id="result-title-$ID">$Title</div><% end_if %>
		<div class="step-content">
			<% if $Content %>$Content<% end_if %>
			<button type="button" class="step-button" data-action="restart-tree" data-target="$Top.Controller.ParentController.Link" aria-label="Start again from the beginning">Start again?</button>
		</div>
	<% end_with %>
</div>
<% end_if %>