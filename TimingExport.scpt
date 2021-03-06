JsOsaDAS1.001.00bplist00�Vscript_�var endDate = new Date();
var startDate = new Date(endDate);
startDate.setDate(startDate.getDate() - 30 /* days */);

// Copyright (c) 2017 timingapp.com / Daniel Alm. All rights reserved.
// This script is licensed only to extend the functionality of Timing. Redistribution and any other uses are not allowed without prior permission from us.
var helper = Application("TimingHelper");
var app = Application.currentApplication();
app.includeStandardAdditions = true;

var taskListPath = app.chooseFileName({ withPrompt: "Select which file to write the project list to.", defaultName: "TimingTasks.csv" }).toString();

$.NSFileManager.defaultManager.createDirectoryAtPathWithIntermediateDirectoriesAttributesError($(taskListPath).stringByDeletingLastPathComponent.stringByStandardizingPath, true, $(), $());

var reportSettings = helper.ReportSettings().make();
var exportSettings = helper.ExportSettings().make();

reportSettings.firstGroupingMode = "raw";
reportSettings.tasksIncluded = true;
reportSettings.appUsageIncluded = false;

exportSettings.fileFormat = "CSV";
exportSettings.durationFormat = "seconds";
exportSettings.shortEntriesIncluded = true;

var app = Application.currentApplication();
app.includeStandardAdditions = true;

helper.saveReport({ withReportSettings: reportSettings, exportSettings: exportSettings, between: startDate, and: endDate, to: Path($(taskListPath).stringByStandardizingPath.js) });

helper.delete(reportSettings);
helper.delete(exportSettings);                              �jscr  ��ޭ