
{
  description = "Hardware design using Go.";

  inputs = {
    nixpkgs.url = "github:nixos/nixpkgs/nixos-unstable";

    flake-parts.url = "github:hercules-ci/flake-parts";
    flake-parts.inputs.nixpkgs-lib.follows = "nixpkgs";

    flake-utils.url = "github:numtide/flake-utils";

    treefmt-nix.url = "github:numtide/treefmt-nix";
    treefmt-nix.inputs.nixpkgs.follows = "nixpkgs";
  };

  outputs = inputs @ {
    self,
    flake-utils,
    treefmt-nix,
    ...
  }:
    flake-utils.lib.eachSystem [
      "x86_64-linux"
      "i686-linux"
      "x86_64-darwin"
      "aarch64-linux"
      "aarch64-darwin"
    ] (system: let
      overlays = [(final: prev: {go = prev.go_1_24;})];
      pkgs = import inputs.nixpkgs {inherit system overlays;};

      scripts = {
        dx = {
          exec = ''$EDITOR $REPO_ROOT/flake.nix'';
          description = "Edit flake.nix";
        };
      };

      # Convert scripts to packages
      scriptPackages =
        pkgs.lib.mapAttrsToList
        (name: script: pkgs.writeShellScriptBin name script.exec)
        scripts;

      # Configure treefmt
      treefmtConfig = {
        projectRootFile = "flake.nix";
        programs = {
          # Go formatting
          gofmt.enable = true;

          # Nix formatting
          alejandra.enable = true;

          # Other formatters via prettier
          prettier = {
            enable = true;
            includes = ["*.js" "*.ts" "*.css" "*.md" "*.json"];
          };
        };
      };

      treefmtEval = treefmt-nix.lib.evalModule pkgs treefmtConfig;

    in {

      # Formatter output for `nix fmt`
      formatter = treefmtEval.config.build.wrapper;

      devShells.default = pkgs.mkShell {
        shellHook = ''
          export REPO_ROOT=$(git rev-parse --show-toplevel)
          export CGO_CFLAGS="-O2"
        '';
        packages = with pkgs;
          [
            # Nix
            alejandra
            nixd

            # Go Tools
            air

            # Formatters
            prettierd

            # LSP and Editor Integration Testing
            neovim
            lua-language-server

            # Container runtime for VHS demos
            podman
						duckdb

            # treefmt
            treefmtEval.config.build.wrapper
          ]
          ++ scriptPackages;
      };
    });
}
