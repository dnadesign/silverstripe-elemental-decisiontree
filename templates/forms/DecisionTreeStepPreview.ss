<% with $Step %>
<table class="decisiontree-table">
	<tbody>
		<tr>
			<td colspan="$Answers.Count">
				<div class="question<% if Type="Result" %> question--result<% end_if %> <% if $IsCurrentlyEdited %>question--current<% end_if %>" id="q{$ID}">
					<% if $IsCurrentlyEdited %>
						$Title
					<% else %>
						<a href="$CMSEditLink">$Title</a>
					<% end_if %>
				</div>
			</td>
		</tr>
		<tr class="tr-answers">
			<% loop $Answers %>
				<td><div class="answer answerfor-{$Question.ID}" data-answerfor="q"><a href="$CMSEditLink">$Title</a></div>
					<% if $ResultingStep %>
						<% include DecisionTreeStepPreview Step=$ResultingStep %>
					<% else %>
						<table class="decisiontree-table">
							<tbody>
								<tr>
									<td>
										<div class="question question--ghost"><a href="$CMSAddStepLink">Add a step</a></div>
									</td>
								</tr>
							</tbody>
						</table>
					<% end_if %>
				</td>
			<% end_loop %>
		</tr>
	</tbody>
</table>
<% end_with %>