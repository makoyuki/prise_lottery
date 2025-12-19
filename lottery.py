#!/usr/bin/env python3
# -*- coding: utf-8 -*-

import pandas as pd
import random
import sys
import os
from datetime import datetime

def main():
    print("=== 賞品抽選システム ===")

    # CSVファイルの存在確認
    prizes_file = 'prizes.csv'
    applicants_file = 'applicants.csv'

    if not os.path.exists(prizes_file):
        print(f"エラー: {prizes_file} が見つかりません")
        create_sample_files()
        return

    if not os.path.exists(applicants_file):
        print(f"エラー: {applicants_file} が見つかりません")
        create_sample_files()
        return

    # 抽選システム実行
    lottery = PrizeLottery(prizes_file, applicants_file)

    print(f"賞品数: {len(lottery.prizes)}")
    print(f"申込者数: {len(lottery.applicants)}")

    # 実行確認
    response = input("\n抽選を実行しますか？ (y/N): ")
    if response.lower() != 'y':
        print("キャンセルされました")
        return

    # 抽選実行
    results = lottery.conduct_lottery()

    # 結果出力
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    output_file = f"lottery_results_{timestamp}.csv"

    lottery.print_summary(results)
    lottery.export_results(results, output_file)

    print(f"\n抽選完了！結果は {output_file} に保存されました。")

def create_sample_files():
    """サンプルファイルを作成"""
    print("\nサンプルファイルを作成しますか？")
    response = input("prizes.csv と applicants.csv を作成 (y/N): ")

    if response.lower() == 'y':
        # 賞品ファイル作成
        prizes_data = []
        for i in range(1, 61):
            prizes_data.append({
                'prize_id': f'PC{i:03d}',
                'prize_name': f'ノートPC-{i}'
            })

        df_prizes = pd.DataFrame(prizes_data)
        df_prizes.to_csv('prizes.csv', index=False, encoding='utf-8-sig')

        # 申込者ファイル作成（サンプル）
        applicants_data = []
        prize_ids = [f'PC{i:03d}' for i in range(1, 61)]

        for i in range(1, 151):  # 150名のサンプル
            preferences = random.sample(prize_ids, 5)
            applicants_data.append({
                'applicant_id': f'U{i:04d}',
                'name': f'申込者{i:03d}',
                'choice_1': preferences[0],
                'choice_2': preferences[1],
                'choice_3': preferences[2],
                'choice_4': preferences[3],
                'choice_5': preferences[4]
            })

        df_applicants = pd.DataFrame(applicants_data)
        df_applicants.to_csv('applicants.csv', index=False, encoding='utf-8-sig')

        print("サンプルファイルを作成しました:")
        print("- prizes.csv (60台のノートPC)")
        print("- applicants.csv (150名の申込者)")
        print("\nファイルを編集後、再度実行してください。")

class PrizeLottery:
    # 前回のコードと同じ内容
    def __init__(self, prizes_file=None, applicants_file=None):
        self.prizes = {}
        self.applicants = {}
        self.winners = {}
        self.remaining_prizes = set()
        self.remaining_applicants = set()

        if prizes_file:
            self.load_prizes_from_csv(prizes_file)
        if applicants_file:
            self.load_applicants_from_csv(applicants_file)

    def load_prizes_from_csv(self, file_path):
        try:
            df = pd.read_csv(file_path, encoding='utf-8-sig')
            for _, row in df.iterrows():
                self.prizes[row['prize_id']] = row['prize_name']
            self.remaining_prizes = set(self.prizes.keys())
        except Exception as e:
            print(f"賞品ファイル読み込みエラー: {e}")
            sys.exit(1)

    def load_applicants_from_csv(self, file_path):
        try:
            df = pd.read_csv(file_path, encoding='utf-8-sig')
            for _, row in df.iterrows():
                preferences = []
                for i in range(1, 6):
                    col_name = f'choice_{i}'
                    if col_name in row and pd.notna(row[col_name]):
                        preferences.append(row[col_name])

                self.applicants[row['applicant_id']] = {
                    'name': row['name'],
                    'preferences': preferences
                }
            self.remaining_applicants = set(self.applicants.keys())
        except Exception as e:
            print(f"申込者ファイル読み込みエラー: {e}")
            sys.exit(1)

    def conduct_lottery(self, random_seed=None):
        if random_seed:
            random.seed(random_seed)

        results = []
        max_preference = 5

        for preference_rank in range(1, max_preference + 1):
            print(f"\n=== 第{preference_rank}希望の抽選中... ===")

            if not self.remaining_prizes or not self.remaining_applicants:
                break

            prizes_to_process = list(self.remaining_prizes)
            round_winners = 0

            for prize_id in prizes_to_process:
                candidates = []
                for applicant_id in self.remaining_applicants:
                    preferences = self.applicants[applicant_id]['preferences']
                    if (len(preferences) >= preference_rank and
                        preferences[preference_rank - 1] == prize_id):
                        candidates.append(applicant_id)

                if candidates:
                    winner_id = random.choice(candidates)
                    winner_name = self.applicants[winner_id]['name']
                    prize_name = self.prizes[prize_id]

                    self.winners[prize_id] = winner_id
                    result = {
                        'preference_rank': preference_rank,
                        'prize_id': prize_id,
                        'prize_name': prize_name,
                        'winner_id': winner_id,
                        'winner_name': winner_name,
                        'candidates_count': len(candidates)
                    }
                    results.append(result)

                    self.remaining_applicants.remove(winner_id)
                    self.remaining_prizes.remove(prize_id)
                    round_winners += 1

            print(f"第{preference_rank}希望で {round_winners} 名が当選")

        return results

    def export_results(self, results, output_file):
        df = pd.DataFrame(results)
        df.to_csv(output_file, index=False, encoding='utf-8-sig')

    def print_summary(self, results):
        print(f"\n=== 抽選結果サマリー ===")
        print(f"当選者数: {len(results)}")
        print(f"残り賞品: {len(self.remaining_prizes)}")
        print(f"未当選者: {len(self.remaining_applicants)}")

if __name__ == "__main__":
    main()
