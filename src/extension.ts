import * as vscode from 'vscode';

export function activate(context: vscode.ExtensionContext) {
	const disposable = vscode.commands.registerCommand('minimum-de-commande.helloWorld', () => {
		vscode.window.showInformationMessage('Minimum de Commande est actif !');
	});

	context.subscriptions.push(disposable);
}

export function deactivate() {}
